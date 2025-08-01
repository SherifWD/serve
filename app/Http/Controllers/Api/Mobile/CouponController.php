<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CouponController extends Controller
{
    public function verify(Request $request)
    {
        
    Log::info('Coupon Request', $request->all());

        $data = $request->validate([
            'code' => 'required|string'
        ]);
        $coupon = Coupon::where('code', $data['code'])
            ->where('active', true)
            ->where(function($q) {
                $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', now());
            })
            ->first();

        if (!$coupon) {
            return response()->json(['valid' => false], 404);
        }

        return response()->json([
            'valid' => true,
            'type' => $coupon->type,
            'value' => $coupon->value,
            'expiry_date' => $coupon->expiry_date
        ]);
    }

    // POST /api/coupons (to create a coupon for testing)
    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|unique:coupons,code',
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0.01',
            'expiry_date' => 'nullable|date'
        ]);
        $coupon = Coupon::create($data);
        return response()->json($coupon, 201);
    }
}
