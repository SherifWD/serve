<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FiscalProfile;
use App\Models\Restaurant;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RestaurantController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Restaurant::query()
            ->with('fiscalProfiles:id,restaurant_id,branch_id,currency_code,is_default')
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
            'currency_code' => 'nullable|string|size:3',
            'logo_url' => 'nullable|string|max:2048',
        ]);
        $data['currency_code'] = $this->normalizeCurrency($data['currency_code'] ?? null);

        return DB::transaction(function () use ($data) {
            $restaurant = Restaurant::create($data);
            $this->syncRestaurantCurrency($restaurant, $data['currency_code']);

            return response()->json($restaurant->fresh(['fiscalProfiles']), 201);
        });
    }

    public function show(Request $request, Restaurant $restaurant)
    {
        $user = $request->user();

        abort_unless(
            $user->isPlatformAdmin() || (int) $user->restaurant_id === (int) $restaurant->id,
            403,
            'You cannot view this restaurant.'
        );

        return $restaurant->load('fiscalProfiles:id,restaurant_id,branch_id,currency_code,is_default')
            ->loadCount(['branches', 'users']);
    }

    public function update(Request $request, Restaurant $restaurant)
    {
        abort_unless($request->user()->isPlatformAdmin(), 403, 'Only platform admins can update restaurants.');

        $data = $request->validate([
            'name' => 'required|string|max:255|unique:restaurants,name,'.$restaurant->id,
            'kind' => 'required|in:restaurant,cafe',
            'currency_code' => 'nullable|string|size:3',
            'logo_url' => 'nullable|string|max:2048',
        ]);
        $data['currency_code'] = $this->normalizeCurrency($data['currency_code'] ?? $restaurant->currency_code ?? null);

        DB::transaction(function () use ($restaurant, $data) {
            $restaurant->update($data);
            $this->syncRestaurantCurrency($restaurant, $data['currency_code']);
        });

        return $restaurant->fresh(['branches', 'fiscalProfiles']);
    }

    public function destroy(Request $request, Restaurant $restaurant)
    {
        abort_unless($request->user()->isPlatformAdmin(), 403, 'Only platform admins can delete restaurants.');

        $restaurant->delete();

        return response()->json(['message' => 'Restaurant deleted']);
    }

    private function normalizeCurrency(?string $currency): string
    {
        $currency = strtoupper(trim((string) $currency));

        return strlen($currency) === 3 ? $currency : 'USD';
    }

    private function syncRestaurantCurrency(Restaurant $restaurant, string $currency): void
    {
        Setting::query()->updateOrCreate(
            ['key' => "restaurant.{$restaurant->id}.currency"],
            ['value' => $currency],
        );

        FiscalProfile::query()->updateOrCreate(
            [
                'restaurant_id' => $restaurant->id,
                'branch_id' => null,
            ],
            [
                'display_name' => $restaurant->name.' fiscal profile',
                'is_default' => true,
                'currency_code' => $currency,
                'eta_seller_name' => $restaurant->name,
            ],
        );
    }
}
