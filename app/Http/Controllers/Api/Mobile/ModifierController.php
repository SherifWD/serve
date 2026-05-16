<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Modifier;
use Illuminate\Http\Request;

class ModifierController extends Controller
{
    public function availableForWaiter(Request $request)
{
    $user = $request->user();
    $branchId = $user->branch_id;
    $restaurantId = $user->restaurant_id ?: ($branchId
        ? Branch::query()->whereKey($branchId)->value('restaurant_id')
        : null);

    $modifiers = Modifier::query()
        ->select('id', 'name', 'price', 'restaurant_id', 'category_id')
        ->where('is_active', true)
        ->where(function ($query) use ($restaurantId) {
            $query->whereNull('restaurant_id');

            if ($restaurantId) {
                $query->orWhere('restaurant_id', $restaurantId);
            }
        })
        ->orderBy('name')
        ->get();

    return response()->json(['data' => $modifiers]);
}
}
