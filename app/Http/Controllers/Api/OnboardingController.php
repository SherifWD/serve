<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BillingInvoice;
use App\Models\Branch;
use App\Models\FiscalProfile;
use App\Models\Permission;
use App\Models\Restaurant;
use App\Models\RestaurantSubscription;
use App\Models\Role;
use App\Models\Setting;
use App\Models\SubscriptionPlan;
use App\Models\Type;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OnboardingController extends Controller
{
    public function storeRestaurant(Request $request)
    {
        abort_unless($request->user()?->hasPermission('platform.restaurants.manage'), 403, 'Only platform admins can onboard restaurants.');

        $data = $request->validate([
            'restaurant.name' => 'required|string|max:255|unique:restaurants,name',
            'restaurant.kind' => 'required|in:restaurant,cafe',
            'branches' => 'required|array|min:1',
            'branches.*.name' => 'required|string|max:255',
            'branches.*.location' => 'nullable|string|max:255',
            'owner.name' => 'required|string|max:255',
            'owner.email' => 'required|email|unique:users,email',
            'owner.password' => 'nullable|string|min:8',
            'plan_id' => 'nullable|integer|exists:subscription_plans,id',
            'settings.currency' => 'nullable|string|max:10',
            'settings.vat_rate' => 'nullable|numeric|min:0|max:1',
            'settings.price_includes_vat' => 'nullable|boolean',
            'fiscal.eta_seller_rin' => 'nullable|string|max:30',
            'fiscal.eta_seller_name' => 'nullable|string|max:200',
            'fiscal.eta_activity_code' => 'nullable|string|max:10',
            'fiscal.address_governate' => 'nullable|string|max:100',
            'fiscal.address_region_city' => 'nullable|string|max:100',
            'fiscal.address_street' => 'nullable|string|max:200',
            'fiscal.address_building_number' => 'nullable|string|max:100',
        ]);

        $password = $data['owner']['password'] ?? Str::password(12);

        $result = DB::transaction(function () use ($data, $password) {
            $this->ensureReferenceData();

            $restaurant = Restaurant::query()->create([
                'name' => $data['restaurant']['name'],
                'kind' => $data['restaurant']['kind'],
            ]);

            $branches = collect($data['branches'])
                ->map(fn (array $branch) => Branch::query()->create([
                    'restaurant_id' => $restaurant->id,
                    'name' => $branch['name'],
                    'location' => $branch['location'] ?? null,
                ]))
                ->values();

            $owner = User::query()->create([
                'name' => $data['owner']['name'],
                'email' => $data['owner']['email'],
                'password' => Hash::make($password),
                'role' => 'owner',
                'restaurant_id' => $restaurant->id,
                'branch_id' => null,
            ]);

            $ownerRole = Role::query()->where('name', 'owner')->firstOrFail();
            $ownerType = Type::query()->where('name', 'owner')->firstOrFail();
            $owner->roles()->sync([$ownerRole->id]);
            $owner->types()->sync([$ownerType->id]);

            Setting::query()->updateOrCreate(
                ['key' => "restaurant.{$restaurant->id}.currency"],
                ['value' => $data['settings']['currency'] ?? 'EGP'],
            );
            Setting::query()->updateOrCreate(
                ['key' => "restaurant.{$restaurant->id}.vat_rate"],
                ['value' => (string) ($data['settings']['vat_rate'] ?? '0.14')],
            );
            FiscalProfile::query()->create([
                'restaurant_id' => $restaurant->id,
                'branch_id' => null,
                'display_name' => $restaurant->name.' fiscal profile',
                'is_default' => true,
                'currency_code' => strtoupper($data['settings']['currency'] ?? 'EGP'),
                'vat_rate' => $data['settings']['vat_rate'] ?? 0.14,
                'price_includes_vat' => $data['settings']['price_includes_vat'] ?? true,
                'eta_seller_rin' => $data['fiscal']['eta_seller_rin'] ?? null,
                'eta_seller_name' => $data['fiscal']['eta_seller_name'] ?? $restaurant->name,
                'eta_activity_code' => $data['fiscal']['eta_activity_code'] ?? null,
                'address_governate' => $data['fiscal']['address_governate'] ?? null,
                'address_region_city' => $data['fiscal']['address_region_city'] ?? null,
                'address_street' => $data['fiscal']['address_street'] ?? null,
                'address_building_number' => $data['fiscal']['address_building_number'] ?? null,
            ]);

            $plan = isset($data['plan_id'])
                ? SubscriptionPlan::query()->find($data['plan_id'])
                : SubscriptionPlan::query()->where('slug', 'starter-pos')->first();
            $subscription = null;
            $invoice = null;

            if ($plan) {
                $subscription = RestaurantSubscription::query()->create([
                    'restaurant_id' => $restaurant->id,
                    'subscription_plan_id' => $plan->id,
                    'status' => 'trialing',
                    'trial_ends_at' => now()->addDays(14),
                    'current_period_starts_at' => now(),
                    'current_period_ends_at' => now()->addMonth(),
                    'next_invoice_at' => now()->addMonth(),
                    'billing_email' => $owner->email,
                    'metadata' => ['created_from' => 'restaurant_onboarding'],
                ]);

                $invoice = BillingInvoice::query()->create([
                    'restaurant_id' => $restaurant->id,
                    'restaurant_subscription_id' => $subscription->id,
                    'invoice_number' => 'INV-'.$restaurant->id.'-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                    'status' => $plan->price > 0 ? 'open' : 'paid',
                    'currency' => $plan->currency,
                    'subtotal' => $plan->price,
                    'tax' => 0,
                    'total' => $plan->price,
                    'due_date' => now()->addDays(7)->toDateString(),
                    'paid_at' => $plan->price > 0 ? null : now(),
                    'line_items' => [[
                        'description' => $plan->name.' subscription',
                        'quantity' => 1,
                        'unit_amount' => (float) $plan->price,
                        'amount' => (float) $plan->price,
                    ]],
                    'metadata' => ['created_from' => 'restaurant_onboarding'],
                ]);
            }

            return [
                'restaurant' => $restaurant->loadCount(['branches', 'users']),
                'branches' => $branches,
                'owner' => $owner->fresh(['roles', 'types', 'restaurant']),
                'subscription' => $subscription?->load('plan'),
                'invoice' => $invoice,
            ];
        });

        return response()->json([
            ...$result,
            'temporary_password' => $data['owner']['password'] ?? $password,
        ], 201);
    }

    private function ensureReferenceData(): void
    {
        $permissions = [
            'platform.restaurants.manage',
            'dashboard.view',
            'branches.view',
            'branches.manage',
            'users.view',
            'users.manage',
            'roles.view',
            'orders.view',
            'orders.manage',
            'customers.view',
            'customers.manage',
            'cashier.manage',
            'kds.manage',
            'tables.view',
            'tables.manage',
            'menu.view',
            'menu.manage',
            'categories.view',
            'categories.manage',
            'products.view',
            'products.manage',
            'inventory.view',
            'inventory.manage',
            'suppliers.view',
            'suppliers.manage',
            'ingredients.view',
            'ingredients.manage',
            'recipes.view',
            'recipes.manage',
            'employees.view',
            'employees.manage',
            'settings.view',
            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission]);
        }

        $ownerRole = Role::query()->firstOrCreate(['name' => 'owner']);
        $ownerPermissionIds = Permission::query()
            ->whereIn('name', array_values(array_filter($permissions, fn ($permission) => !str_starts_with($permission, 'platform.'))))
            ->pluck('id')
            ->all();
        $ownerRole->permissions()->syncWithoutDetaching($ownerPermissionIds);

        Type::query()->firstOrCreate(['name' => 'owner']);
        Type::query()->firstOrCreate(['name' => 'waiter']);
        Type::query()->firstOrCreate(['name' => 'cashier']);
        Type::query()->firstOrCreate(['name' => 'kitchen']);

        SubscriptionPlan::query()->firstOrCreate(
            ['slug' => 'starter-pos'],
            [
                'name' => 'Starter POS',
                'billing_period' => 'monthly',
                'currency' => 'EGP',
                'price' => 1499,
                'max_branches' => 1,
                'max_users' => 8,
                'max_devices' => 4,
                'features' => ['waiter_app', 'cashier_app', 'kds', 'basic_inventory', 'fiscal_exports'],
                'is_active' => true,
            ],
        );
    }
}
