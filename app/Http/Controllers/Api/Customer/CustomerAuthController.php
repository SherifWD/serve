<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerOtpCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerAuthController extends Controller
{
    public function login(Request $request)
    {
        return $this->requestOtp($request);
    }

    public function requestOtp(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required_without:email|string|max:30',
            'email' => 'nullable|email|max:255',
            'name' => 'nullable|string|max:255',
            'channel' => 'nullable|in:sms,email',
        ]);

        $customer = $this->findOrCreateCustomer($data);
        $channel = $data['channel'] ?? (!empty($data['email']) && empty($data['phone']) ? 'email' : 'sms');
        $destination = $channel === 'email'
            ? strtolower((string) ($data['email'] ?? $customer->email))
            : (string) ($data['phone'] ?? $customer->phone);

        if (blank($destination)) {
            return response()->json([
                'message' => 'A phone number or email is required for verification.',
            ], 422);
        }

        $code = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(10);

        CustomerOtpCode::query()
            ->where('customer_id', $customer->id)
            ->where('purpose', 'login')
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        CustomerOtpCode::create([
            'customer_id' => $customer->id,
            'channel' => $channel,
            'destination' => $destination,
            'purpose' => 'login',
            'code_hash' => Hash::make($code),
            'expires_at' => $expiresAt,
            'metadata' => [
                'ip' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ],
        ]);

        $payload = [
            'otp_required' => true,
            'customer' => $this->serializeCustomer($customer),
            'verification' => [
                'channel' => $channel,
                'destination' => $destination,
                'expires_at' => $expiresAt->toISOString(),
                'delivery_status' => 'queued',
            ],
        ];

        if (app()->environment(['local', 'testing'])) {
            $payload['debug_otp_code'] = $code;
        }

        return response()->json($payload, 202);
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required_without:email|string|max:30',
            'email' => 'nullable|email|max:255',
            'code' => 'required|string|min:4|max:10',
            'channel' => 'nullable|in:sms,email',
        ]);

        $customer = Customer::query()
            ->when(
                !empty($data['phone']),
                fn ($query) => $query->where('phone', $data['phone'])
            )
            ->when(
                empty($data['phone']) && !empty($data['email']),
                fn ($query) => $query->where('email', strtolower($data['email']))
            )
            ->first();

        if (!$customer) {
            return response()->json(['message' => 'Verification code is invalid or expired.'], 422);
        }

        $channel = $data['channel'] ?? (!empty($data['phone']) ? 'sms' : 'email');
        $destination = $channel === 'email' ? strtolower((string) ($data['email'] ?? '')) : (string) ($data['phone'] ?? '');
        if (blank($destination)) {
            return response()->json(['message' => 'Verification code is invalid or expired.'], 422);
        }

        $otp = CustomerOtpCode::query()
            ->where('customer_id', $customer->id)
            ->where('destination', $destination)
            ->where('purpose', 'login')
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (!$otp || !Hash::check($data['code'], $otp->code_hash)) {
            $otp?->increment('attempts');

            return response()->json(['message' => 'Verification code is invalid or expired.'], 422);
        }

        $otp->forceFill(['consumed_at' => now()])->save();
        $customer->forceFill(
            $otp->channel === 'email'
                ? ['email_verified_at' => now()]
                : ['phone_verified_at' => now()]
        )->save();

        $token = $customer->createToken('customer-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'customer' => $this->serializeCustomer($customer->fresh()),
        ]);
    }

    public function me(Request $request)
    {
        /** @var \App\Models\Customer $customer */
        $customer = $request->user();

        return response()->json([
            'customer' => $this->serializeCustomer($customer),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out']);
    }

    private function findOrCreateCustomer(array $data): Customer
    {
        $customer = Customer::query()
            ->when(
                !empty($data['phone']),
                fn ($query) => $query->where('phone', $data['phone'])
            )
            ->when(
                empty($data['phone']) && !empty($data['email']),
                fn ($query) => $query->where('email', strtolower($data['email']))
            )
            ->first();

        if (!$customer) {
            return Customer::create([
                'name' => $data['name'] ?? 'Guest',
                'phone' => $data['phone'] ?? null,
                'email' => isset($data['email']) ? strtolower($data['email']) : null,
            ]);
        }

        $customer->fill(array_filter([
            'name' => $data['name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => isset($data['email']) ? strtolower($data['email']) : null,
        ], fn ($value) => filled($value)))->save();

        return $customer;
    }

    private function serializeCustomer(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'phone_verified_at' => $customer->phone_verified_at?->toISOString(),
            'email_verified_at' => $customer->email_verified_at?->toISOString(),
            'loyalty_points' => $customer->loyalty_points,
        ];
    }
}
