<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Restaurant::query()
            ->withCount('branches')
            ->withCount('users')
            ->orderBy('name');

        if ($user->isPlatformAdmin()) {
            return $query->get();
        }

        if ($user->restaurant_id) {
            return $query->whereKey($user->restaurant_id)->get();
        }

        return collect();
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isPlatformAdmin(), 403, 'Only platform admins can create restaurants.');

        $data = $request->validate([
            'name' => 'required|string|max:255|unique:restaurants,name',
            'kind' => 'required|in:restaurant,cafe',
        ]);

        return Restaurant::create($data);
    }

    public function show(Request $request, Restaurant $restaurant)
    {
        $user = $request->user();

        abort_unless(
            $user->isPlatformAdmin() || (int) $user->restaurant_id === (int) $restaurant->id,
            403,
            'You cannot view this restaurant.'
        );

        return $restaurant->loadCount(['branches', 'users']);
    }

    public function update(Request $request, Restaurant $restaurant)
    {
        abort_unless($request->user()->isPlatformAdmin(), 403, 'Only platform admins can update restaurants.');

        $data = $request->validate([
            'name' => 'required|string|max:255|unique:restaurants,name,'.$restaurant->id,
            'kind' => 'required|in:restaurant,cafe',
        ]);

        $restaurant->update($data);

        return $restaurant->fresh(['branches']);
    }

    public function destroy(Request $request, Restaurant $restaurant)
    {
        abort_unless($request->user()->isPlatformAdmin(), 403, 'Only platform admins can delete restaurants.');

        $restaurant->delete();

        return response()->json(['message' => 'Restaurant deleted']);
    }
}
