<?php

namespace App\Http\Controllers;

use App\Models\MarketingInquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

class MarketingInquiryPublicController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'business_name' => ['required', 'string', 'max:160'],
            'role' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:160'],
            'phone' => ['required', 'string', 'max:40'],
            'city' => ['nullable', 'string', 'max:120'],
            'website' => ['nullable', 'string', 'max:180'],
            'business_type' => ['required', Rule::in(['cafe', 'restaurant', 'cloud-kitchen', 'bakery', 'multi-concept', 'other'])],
            'branch_count' => ['nullable', 'integer', 'min:1', 'max:500'],
            'staff_count' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'current_system' => ['nullable', 'string', 'max:160'],
            'order_channels' => ['nullable', 'array'],
            'order_channels.*' => ['string', 'max:80'],
            'interest_areas' => ['nullable', 'array'],
            'interest_areas.*' => ['string', 'max:80'],
            'devices' => ['nullable', 'array'],
            'devices.*' => ['string', 'max:80'],
            'timeline' => ['required', Rule::in(['this-month', '1-3-months', '3-6-months', 'researching'])],
            'budget_range' => ['nullable', 'string', 'max:80'],
            'pain_points' => ['required', 'string', 'max:2000'],
            'success_notes' => ['nullable', 'string', 'max:1600'],
            'preferred_contact_method' => ['required', Rule::in(['phone', 'whatsapp', 'email'])],
            'best_contact_time' => ['nullable', 'string', 'max:120'],
            'consent_to_contact' => ['accepted'],
        ]);

        $data['source_url'] = $request->headers->get('referer');
        $data['ip_address'] = $request->ip();
        $data['user_agent'] = substr((string) $request->userAgent(), 0, 500);
        $data['status'] = 'new';

        $inquiry = MarketingInquiry::query()->create($data);

        $this->notifySalesInbox($inquiry);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Inquiry received.',
                'data' => ['id' => $inquiry->id],
            ], 201);
        }

        return redirect()
            ->route('marketing.contact')
            ->with('status', 'Thanks. Your survey was received and the Janova team can now review your requirements.');
    }

    private function notifySalesInbox(MarketingInquiry $inquiry): void
    {
        try {
            Mail::raw($this->emailBody($inquiry), function ($message) use ($inquiry): void {
                $message
                    ->to('pos@janovatech.com')
                    ->replyTo($inquiry->email, $inquiry->full_name)
                    ->subject("Janova POS survey: {$inquiry->business_name}");
            });
        } catch (Throwable $exception) {
            Log::warning('Marketing inquiry email notification failed.', [
                'marketing_inquiry_id' => $inquiry->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function emailBody(MarketingInquiry $inquiry): string
    {
        $lines = [
            'New Janova Serve POS survey submission',
            '',
            "Inquiry ID: {$inquiry->id}",
            "Business: {$inquiry->business_name}",
            "Contact: {$inquiry->full_name}",
            "Role: ".($inquiry->role ?: 'Not provided'),
            "Email: {$inquiry->email}",
            "Phone: {$inquiry->phone}",
            "City: ".($inquiry->city ?: 'Not provided'),
            "Website: ".($inquiry->website ?: 'Not provided'),
            '',
            "Business type: {$inquiry->business_type}",
            "Branches: ".($inquiry->branch_count ?: 'Not provided'),
            "Staff: ".($inquiry->staff_count ?: 'Not provided'),
            "Current system: ".($inquiry->current_system ?: 'Not provided'),
            "Order channels: ".$this->joinList($inquiry->order_channels),
            "Interested modules: ".$this->joinList($inquiry->interest_areas),
            "Devices: ".$this->joinList($inquiry->devices),
            '',
            "Timeline: {$inquiry->timeline}",
            "Budget range: ".($inquiry->budget_range ?: 'Not provided'),
            "Preferred contact: {$inquiry->preferred_contact_method}",
            "Best contact time: ".($inquiry->best_contact_time ?: 'Not provided'),
            '',
            'Pain points:',
            $inquiry->pain_points,
            '',
            'Success notes:',
            $inquiry->success_notes ?: 'Not provided',
        ];

        return implode("\n", $lines);
    }

    private function joinList(?array $items): string
    {
        return empty($items) ? 'Not provided' : implode(', ', $items);
    }
}
