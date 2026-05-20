<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\PaymentProviderConfig;
use App\Support\HardwareValidation;
use Illuminate\Http\Request;

class PaymentAttemptController extends Controller
{
    use EnforcesTenantAccess;

    public function store(Request $request)
    {
        $data = $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'device_uuid' => 'nullable|string|max:255',
            'provider' => 'nullable|string|max:100',
            'method' => 'required|in:cash,card,wallet',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'provider_reference' => 'nullable|string|max:255',
            'request_payload' => 'nullable|array',
            'client_mutation_id' => 'nullable|string|max:255',
        ]);

        $order = Order::query()->with('branch')->findOrFail($data['order_id']);
        $this->ensureBranchAccess($request, (int) $order->branch_id);
        $device = $this->resolveDevice($data['device_uuid'] ?? null, (int) $order->branch_id);
        $providerName = $data['provider'] ?? $device?->payment_provider ?? null;
        $providerConfig = $this->resolvePaymentProvider($providerName, (int) $order->branch->restaurant_id, (int) $order->branch_id);

        HardwareValidation::validatePaymentProviderForAttempt($providerConfig, $data['method'], $device);

        $status = filled($data['provider_reference'] ?? null) || $data['method'] === 'cash'
            ? 'approved'
            : 'requires_action';

        $attempt = PaymentAttempt::query()->create([
            'order_id' => $order->id,
            'restaurant_id' => $order->branch->restaurant_id,
            'branch_id' => $order->branch_id,
            'device_id' => $device?->id,
            'provider' => $providerName ?? 'manual',
            'method' => $data['method'],
            'amount' => $data['amount'],
            'currency' => strtoupper($data['currency'] ?? 'USD'),
            'status' => $status,
            'provider_reference' => $data['provider_reference'] ?? null,
            'request_payload' => $data['request_payload'] ?? null,
            'client_mutation_id' => $request->header('X-Client-Mutation-Id') ?: ($data['client_mutation_id'] ?? null),
            'attempted_at' => now(),
        ]);

        return response()->json(['data' => $attempt->fresh(['order', 'device'])], 201);
    }

    public function update(Request $request, PaymentAttempt $paymentAttempt)
    {
        $attempt = PaymentAttempt::query()
            ->with(['order.branch', 'payment'])
            ->findOrFail($paymentAttempt->id);
        $this->ensureBranchAccess($request, (int) $attempt->branch_id);

        $data = $request->validate([
            'status' => 'required|in:requires_action,approved,captured,failed,cancelled',
            'provider_reference' => 'nullable|string|max:255',
            'response_payload' => 'nullable|array',
            'record_payment' => 'nullable|boolean',
        ]);

        $attempt->forceFill([
            'status' => $data['status'],
            'provider_reference' => $data['provider_reference'] ?? $attempt->provider_reference,
            'response_payload' => $data['response_payload'] ?? $attempt->response_payload,
            'captured_at' => in_array($data['status'], ['approved', 'captured'], true) ? now() : $attempt->captured_at,
        ])->save();

        if (($data['record_payment'] ?? false) && !$attempt->payment_id && in_array($attempt->status, ['approved', 'captured'], true)) {
            $payment = Payment::query()->create([
                'payment_attempt_id' => $attempt->id,
                'order_id' => $attempt->order_id,
                'device_id' => $attempt->device_id,
                'method' => $attempt->method,
                'provider' => $attempt->provider,
                'provider_reference' => $attempt->provider_reference,
                'client_mutation_id' => $attempt->client_mutation_id,
                'amount' => $attempt->amount,
                'scope' => 'order',
            ]);

            $attempt->forceFill([
                'payment_id' => $payment->id,
                'status' => 'captured',
                'captured_at' => now(),
            ])->save();
        }

        return response()->json(['data' => $attempt->fresh(['payment', 'device'])]);
    }

    private function resolveDevice(?string $uuid, int $branchId): ?Device
    {
        if (!$uuid) {
            return null;
        }

        return Device::query()
            ->where('uuid', $uuid)
            ->where('branch_id', $branchId)
            ->first();
    }

    private function resolvePaymentProvider(?string $provider, int $restaurantId, int $branchId): ?PaymentProviderConfig
    {
        if (!$provider) {
            return null;
        }

        return PaymentProviderConfig::query()
            ->where('restaurant_id', $restaurantId)
            ->where('provider', $provider)
            ->where(fn ($query) => $query->whereNull('branch_id')->orWhere('branch_id', $branchId))
            ->orderByRaw('CASE WHEN branch_id = ? THEN 0 ELSE 1 END', [$branchId])
            ->first();
    }
}
