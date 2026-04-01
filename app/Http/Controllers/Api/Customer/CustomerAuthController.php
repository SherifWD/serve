<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerAuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required_without:email|string|max:30',
            'email' => 'nullable|email|max:255',
            'name' => 'nullable|string|max:255',
        ]);

        $customer = Customer::query()
            ->when(
                !empty($data['phone']),
                fn ($query) => $query->where('phone', $data['phone'])
            )
            ->when(
                empty($data['phone']) && !empty($data['email']),
                fn ($query) => $query->where('email', $data['email'])
            )
            ->first();

        if (!$customer) {
            $customer = Customer::create([
                'name' => $data['name'] ?? 'Guest',
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
            ]);
        } else {
            $customer->fill(array_filter([
                'name' => $data['name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
            ], fn ($value) => filled($value)))->save();
        }

        $token = $customer->createToken('customer-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'loyalty_points' => $customer->loyalty_points,
            ],
        ]);
    }

    public function me(Request $request)
    {
        /** @var \App\Models\Customer $customer */
        $customer = $request->user();

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'loyalty_points' => $customer->loyalty_points,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out']);
    }
}
