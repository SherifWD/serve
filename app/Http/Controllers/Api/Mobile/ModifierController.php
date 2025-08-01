<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ModifierController extends Controller
{
    public function availableForWaiter(Request $request)
{
    $user = $request->user();
    $branchId = $user->branch_id;
    $restaurantId = $user->restaurant_id;

    // Or load branch/restaurant as needed
    $modifiers = \App\Models\Modifier::whereNull('restaurant_id')
        ->orWhere('restaurant_id', $restaurantId)
        ->orderBy('name')
        ->get();

    return response()->json(['data' => $modifiers]);
}
}
