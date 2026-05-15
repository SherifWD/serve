<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Http\Controllers\Controller;
use App\Models\BillingInvoice;
use App\Models\RestaurantSubscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class BillingController extends Controller
{
    use EnforcesTenantAccess;

    public function plans()
    {
        return response()->json([
            'data' => SubscriptionPlan::query()
                ->where('is_active', true)
                ->orderBy('price')
                ->get(),
        ]);
    }

    public function subscription(Request $request)
    {
        $subscription = $this->restaurantScoped(
            $request,
            RestaurantSubscription::query()->with('plan')
        )
            ->latest('id')
            ->first();

        return response()->json([
            'data' => $subscription,
        ]);
    }

    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'restaurant_id' => 'nullable|integer|exists:restaurants,id',
            'plan_id' => 'required|integer|exists:subscription_plans,id',
            'status' => 'nullable|in:trialing,active,past_due,suspended,cancelled',
            'billing_email' => 'nullable|email|max:255',
            'external_reference' => 'nullable|string|max:255',
        ]);

        $plan = SubscriptionPlan::query()
            ->where('is_active', true)
            ->findOrFail($data['plan_id']);
        $restaurantId = $this->restaurantIdForWrite($request, $data['restaurant_id'] ?? null);
        $startsAt = now();
        $endsAt = $this->periodEnd($startsAt, (string) $plan->billing_period);
        $status = $data['status'] ?? ($plan->price > 0 ? 'trialing' : 'active');

        RestaurantSubscription::query()
            ->where('restaurant_id', $restaurantId)
            ->whereIn('status', ['trialing', 'active', 'past_due'])
            ->update([
                'status' => 'cancelled',
                'cancel_at' => $startsAt,
            ]);

        $subscription = RestaurantSubscription::create([
            'restaurant_id' => $restaurantId,
            'subscription_plan_id' => $plan->id,
            'status' => $status,
            'trial_ends_at' => $status === 'trialing' ? $startsAt->copy()->addDays(14) : null,
            'current_period_starts_at' => $startsAt,
            'current_period_ends_at' => $endsAt,
            'next_invoice_at' => $endsAt,
            'billing_email' => $data['billing_email'] ?? $request->user()?->email,
            'external_reference' => $data['external_reference'] ?? null,
            'metadata' => [
                'created_from' => 'phase_4_saas_onboarding',
            ],
        ]);

        $invoice = $this->createInvoice($subscription, $plan);

        return response()->json([
            'data' => $subscription->load('plan'),
            'invoice' => $invoice,
        ], 201);
    }

    public function invoices(Request $request)
    {
        $invoices = $this->restaurantScoped(
            $request,
            BillingInvoice::query()->with('subscription.plan')
        )
            ->latest('id')
            ->paginate((int) $request->integer('per_page', 20));

        return response()->json($invoices);
    }

    public function markInvoicePaid(Request $request, BillingInvoice $invoice)
    {
        $this->ensureRestaurantAccess($request, (int) $invoice->restaurant_id);

        $invoice->forceFill([
            'status' => 'paid',
            'paid_at' => now(),
        ])->save();

        return response()->json([
            'data' => $invoice->fresh(),
        ]);
    }

    private function periodEnd(Carbon $startsAt, string $billingPeriod): Carbon
    {
        return match ($billingPeriod) {
            'annual' => $startsAt->copy()->addYear(),
            default => $startsAt->copy()->addMonth(),
        };
    }

    private function createInvoice(RestaurantSubscription $subscription, SubscriptionPlan $plan): BillingInvoice
    {
        $subtotal = (float) $plan->price;
        $tax = 0.0;
        $total = round($subtotal + $tax, 2);

        return BillingInvoice::create([
            'restaurant_id' => $subscription->restaurant_id,
            'restaurant_subscription_id' => $subscription->id,
            'invoice_number' => $this->invoiceNumber((int) $subscription->restaurant_id),
            'status' => $total > 0 ? 'open' : 'paid',
            'currency' => $plan->currency,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'due_date' => now()->addDays(7)->toDateString(),
            'paid_at' => $total > 0 ? null : now(),
            'line_items' => [[
                'description' => $plan->name.' subscription',
                'quantity' => 1,
                'unit_amount' => $subtotal,
                'amount' => $subtotal,
            ]],
            'metadata' => [
                'plan_slug' => $plan->slug,
                'billing_period' => $plan->billing_period,
            ],
        ]);
    }

    private function invoiceNumber(int $restaurantId): string
    {
        return 'INV-'.$restaurantId.'-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }
}
