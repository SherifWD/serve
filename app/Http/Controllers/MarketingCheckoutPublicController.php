<?php

namespace App\Http\Controllers;

use App\Models\MarketingInquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

class MarketingCheckoutPublicController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'plan' => ['required', Rule::in(['Starter Cafe', 'Cafe + KDS', 'Restaurant Pro'])],
            'price' => ['required', 'string', 'max:80'],
            'region' => ['required', Rule::in(['egypt', 'mena', 'international'])],
            'full_name' => ['required', 'string', 'max:120'],
            'business_name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:160'],
            'phone' => ['required', 'string', 'max:40'],
            'country' => ['required', 'string', 'max:120'],
            'contact_method' => ['required', Rule::in(['phone', 'whatsapp', 'email'])],
            'business_type' => ['required', Rule::in(['small-cafe', 'big-cafe', 'small-restaurant', 'restaurant'])],
            'branch_count' => ['required', 'integer', 'min:1', 'max:10'],
            'checkout_consent' => ['accepted'],
        ]);

        $inquiry = MarketingInquiry::query()->create([
            'full_name' => $data['full_name'],
            'business_name' => $data['business_name'],
            'role' => 'Standard plan checkout',
            'email' => $data['email'],
            'phone' => $data['phone'],
            'city' => $data['country'],
            'business_type' => str_contains($data['business_type'], 'cafe') ? 'cafe' : 'restaurant',
            'branch_count' => $data['branch_count'],
            'interest_areas' => $this->interestAreas($data['plan']),
            'timeline' => 'this-month',
            'budget_range' => "{$data['price']} ({$data['region']})",
            'pain_points' => "Standard checkout started for {$data['plan']}.",
            'success_notes' => "Landing checkout. Business mode: {$data['business_type']}. Paymob handoff pending.",
            'preferred_contact_method' => $data['contact_method'],
            'consent_to_contact' => true,
            'source_url' => $request->headers->get('referer'),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'status' => 'checkout_started',
        ]);

        $this->notifySalesInbox($inquiry, $data);

        $checkoutUrl = config('services.paymob.checkout_url');
        if (blank($checkoutUrl)) {
            return redirect(url('/#checkout'))
                ->with('checkout_error', 'Paymob checkout URL is not configured yet.');
        }

        return redirect()->away($this->paymobUrl($checkoutUrl, [
            'source' => 'janova-serve-landing',
            'lead_id' => $inquiry->id,
            'plan' => $data['plan'],
            'price' => $data['price'],
            'region' => $data['region'],
        ]));
    }

    private function interestAreas(string $plan): array
    {
        return match ($plan) {
            'Starter Cafe' => ['cashier', 'billing', 'hardware'],
            'Cafe + KDS' => ['cashier', 'kds', 'owner-dashboard', 'billing'],
            default => ['waiter', 'kds', 'cashier', 'inventory', 'owner-dashboard', 'billing'],
        };
    }

    private function notifySalesInbox(MarketingInquiry $inquiry, array $checkout): void
    {
        try {
            Mail::raw($this->emailBody($inquiry, $checkout), function ($message) use ($inquiry): void {
                $message
                    ->to('pos@janovatech.com')
                    ->replyTo($inquiry->email, $inquiry->full_name)
                    ->subject("Janova POS checkout: {$inquiry->business_name}");
            });
        } catch (Throwable $exception) {
            Log::warning('Marketing checkout email notification failed.', [
                'marketing_inquiry_id' => $inquiry->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function emailBody(MarketingInquiry $inquiry, array $checkout): string
    {
        return implode("\n", [
            'New Janova Serve POS standard checkout',
            '',
            "Lead ID: {$inquiry->id}",
            "Plan: {$checkout['plan']}",
            "Price: {$checkout['price']}",
            "Region: {$checkout['region']}",
            "Business: {$inquiry->business_name}",
            "Contact: {$inquiry->full_name}",
            "Email: {$inquiry->email}",
            "Phone: {$inquiry->phone}",
            "Country: {$checkout['country']}",
            "Business mode: {$checkout['business_type']}",
            "Branches: {$checkout['branch_count']}",
            "Preferred contact: {$checkout['contact_method']}",
        ]);
    }

    private function paymobUrl(string $checkoutUrl, array $params): string
    {
        return $checkoutUrl.(str_contains($checkoutUrl, '?') ? '&' : '?').http_build_query($params);
    }
}
