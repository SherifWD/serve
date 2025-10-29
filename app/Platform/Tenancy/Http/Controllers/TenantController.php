<?php

namespace App\Platform\Tenancy\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Platform\Modules\Models\Module;
use App\Platform\Modules\Models\ModuleFeature;
use App\Platform\Tenancy\Http\Requests\TenantModuleFeatureUpdateRequest;
use App\Platform\Tenancy\Http\Requests\TenantModuleUpdateRequest;
use App\Platform\Tenancy\Http\Requests\TenantStoreRequest;
use App\Platform\Tenancy\Http\Requests\TenantUpdateRequest;
use App\Platform\Tenancy\Http\Requests\TenantUserStoreRequest;
use App\Platform\Tenancy\Http\Requests\TenantUserUpdateRequest;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TenantController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Tenant::query()
            ->with([
                'modules' => fn ($q) => $q->with('features')->orderBy('name'),
                'moduleFeatures',
                'subscriptions.plan',
            ])
            ->orderBy('name');

        $perPage = $request->integer('per_page', 15);
        $tenants = $query->paginate($perPage);

        $collection = $tenants->getCollection()->map(fn (Tenant $tenant) => $this->transformTenant($tenant));

        return response()->json([
            'data' => $collection,
            'meta' => [
                'current_page' => $tenants->currentPage(),
                'last_page' => $tenants->lastPage(),
                'per_page' => $tenants->perPage(),
                'total' => $tenants->total(),
            ],
        ]);
    }

    public function modules(): JsonResponse
    {
        $modules = Module::query()
            ->with('features')
            ->orderBy('name')
            ->get()
            ->map(fn (Module $module) => [
                'id' => $module->id,
                'key' => $module->key,
                'name' => $module->name,
                'category' => $module->category,
                'is_core' => (bool) $module->is_core,
                'has_mobile_app' => (bool) $module->has_mobile_app,
                'description' => $module->description,
                'features' => $module->features->map(fn (ModuleFeature $feature) => [
                    'id' => $feature->id,
                    'key' => $feature->key,
                    'name' => $feature->name,
                    'category' => $feature->category,
                    'is_default' => (bool) $feature->is_default,
                    'description' => $feature->description,
                ])->values(),
            ]);

        return response()->json(['data' => $modules]);
    }

    public function store(TenantStoreRequest $request): JsonResponse
    {
        $data = $request->validated();

        $tenant = DB::transaction(function () use ($data) {
            $tenant = Tenant::create([
                'name' => $data['name'],
                'industry' => $data['industry'] ?? null,
                'status' => $data['status'] ?? 'active',
                'billing_email' => $data['billing_email'],
                'activated_at' => now(),
            ]);

            $ownerUser = User::create([
                'name' => $data['owner_name'],
                'email' => $data['owner_email'],
                'password' => Hash::make($data['owner_password']),
            ]);

            $tenant->users()->syncWithoutDetaching([
                $ownerUser->id => [
                    'is_primary' => true,
                    'status' => 'active',
                    'invited_at' => now(),
                    'accepted_at' => now(),
                    'settings' => [],
                ],
            ]);

            $ownerRole = Role::where('key', 'owner')->first();
            if ($ownerRole) {
                $ownerUser->roles()->syncWithoutDetaching([
                    $ownerRole->id => [
                        'tenant_id' => $tenant->id,
                        'assigned_at' => now(),
                    ],
                ]);
            }

            return $tenant;
        });

        $tenant->load([
            'modules' => fn ($q) => $q->with('features')->orderBy('name'),
            'moduleFeatures',
            'subscriptions.plan',
            'users.roles',
        ]);

        return response()->json([
            'data' => $this->transformTenant($tenant),
        ], 201);
    }

    public function update(TenantUpdateRequest $request, Tenant $tenant): JsonResponse
    {
        $tenant->fill($request->validated());
        $tenant->save();

        $tenant->refresh()->load([
            'modules' => fn ($q) => $q->with('features')->orderBy('name'),
            'moduleFeatures',
            'subscriptions.plan',
            'users.roles',
        ]);

        return response()->json(['data' => $this->transformTenant($tenant)]);
    }

    public function users(Tenant $tenant): JsonResponse
    {
        $tenant->load(['users.roles']);

        return response()->json([
            'data' => $tenant->users->map(fn (User $user) => $this->transformTenantUser($tenant, $user)),
        ]);
    }

    public function storeUser(TenantUserStoreRequest $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($tenant, $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $tenant->users()->syncWithoutDetaching([
                $user->id => [
                    'status' => $data['status'] ?? 'active',
                    'is_primary' => false,
                    'invited_at' => now(),
                    'accepted_at' => null,
                    'settings' => [
                        'benefits' => $data['benefits'] ?? [],
                    ],
                ],
            ]);

            if (!empty($data['role'])) {
                $role = Role::where('key', $data['role'])->first();
                if ($role) {
                    $user->roles()->syncWithoutDetaching([
                        $role->id => [
                            'tenant_id' => $tenant->id,
                            'assigned_at' => now(),
                        ],
                    ]);
                }
            }

            return $user;
        });

        $tenant->load(['users.roles']);

        return response()->json([
            'data' => $tenant->users->map(fn (User $user) => $this->transformTenantUser($tenant, $user)),
        ], 201);
    }

    public function updateUser(
        TenantUserUpdateRequest $request,
        Tenant $tenant,
        User $user
    ): JsonResponse {
        $exists = $tenant->users()->where('users.id', $user->id)->exists();

        if (!$exists) {
            throw ValidationException::withMessages([
                'user' => 'User does not belong to this factory.',
            ]);
        }

        $data = $request->validated();

        $pivot = $tenant->users()->where('users.id', $user->id)->first()?->pivot;

        $settings = $pivot?->settings ?? [];
        if (!is_array($settings)) {
            $settings = [];
        }

        if (array_key_exists('benefits', $data)) {
            $settings['benefits'] = $data['benefits'] ?? [];
        }

        $tenant->users()->updateExistingPivot($user->id, [
            'status' => $data['status'] ?? ($pivot?->status ?? 'active'),
            'settings' => $settings,
            'accepted_at' => $pivot?->accepted_at,
        ]);

        $tenant->load(['users.roles']);

        return response()->json([
            'data' => $tenant->users->map(fn (User $user) => $this->transformTenantUser($tenant, $user)),
        ]);
    }

    public function updateModule(TenantModuleUpdateRequest $request, Tenant $tenant, Module $module): JsonResponse
    {
        $data = $request->validated();
        $enabled = $data['enabled'] ?? true;

        $module->loadMissing('features');

        if (!$enabled) {
            $tenant->modules()->detach($module->id);
            if ($module->features->isNotEmpty()) {
                $tenant->moduleFeatures()->detach($module->features->pluck('id'));
            }
            $tenant->load([
                'modules' => fn ($q) => $q->with('features')->orderBy('name'),
                'moduleFeatures',
                'subscriptions.plan',
            ]);

            return response()->json(['data' => $this->transformTenant($tenant)]);
        }

        $existingModule = $tenant->modules()->where('module_id', $module->id)->first();
        $existingPivot = $existingModule?->pivot;

        $status = $data['status'] ?? ($existingPivot->status ?? 'active');
        $seatLimit = array_key_exists('seat_limit', $data) ? $data['seat_limit'] : ($existingPivot->seat_limit ?? null);
        $settings = array_key_exists('settings', $data) ? $data['settings'] : ($existingPivot->settings ?? null);

        if (!$existingPivot) {
            $tenant->modules()->attach($module->id, [
                'status' => $status,
                'seat_limit' => $seatLimit,
                'settings' => $settings,
                'activated_at' => $status === 'active' ? now() : null,
                'deactivated_at' => $status === 'suspended' ? now() : null,
            ]);
        } else {
            $tenant->modules()->updateExistingPivot($module->id, [
                'status' => $status,
                'seat_limit' => $seatLimit,
                'settings' => $settings,
                'activated_at' => $status === 'active'
                    ? ($existingPivot->activated_at ?? now())
                    : $existingPivot->activated_at,
                'deactivated_at' => $status === 'suspended' ? now() : null,
            ]);
        }

        $defaultFeatures = $module->features->where('is_default', true);
        if ($defaultFeatures->isNotEmpty()) {
            $syncPayload = $defaultFeatures->mapWithKeys(fn (ModuleFeature $feature) => [
                $feature->id => [
                    'status' => 'enabled',
                    'settings' => null,
                ],
            ])->toArray();

            $tenant->moduleFeatures()->syncWithoutDetaching($syncPayload);
        }

        $tenant->load([
            'modules' => fn ($q) => $q->with('features')->orderBy('name'),
            'moduleFeatures',
            'subscriptions.plan',
        ]);

        return response()->json(['data' => $this->transformTenant($tenant)]);
    }

    public function updateModuleFeature(
        TenantModuleFeatureUpdateRequest $request,
        Tenant $tenant,
        Module $module,
        ModuleFeature $feature
    ): JsonResponse {
        $module->loadMissing('features');

        abort_if($feature->module_id !== $module->id, 404);

        $tenantHasModule = $tenant->modules()->where('module_id', $module->id)->exists();
        abort_unless($tenantHasModule, 422, 'Module must be enabled before configuring features.');

        $data = $request->validated();
        $status = $data['status'];

        if ($status === 'disabled') {
            $tenant->moduleFeatures()->detach($feature->id);
        } else {
            $tenant->moduleFeatures()->syncWithoutDetaching([
                $feature->id => [
                    'status' => $status,
                    'settings' => $data['settings'] ?? null,
                ],
            ]);
        }

        $tenant->load([
            'modules' => fn ($q) => $q->with('features')->orderBy('name'),
            'moduleFeatures',
            'subscriptions.plan',
        ]);

        return response()->json(['data' => $this->transformTenant($tenant)]);
    }

    protected function transformTenant(Tenant $tenant): array
    {
        $tenant->loadMissing(['modules.features', 'moduleFeatures', 'users.roles']);
        $tenantFeatures = $tenant->moduleFeatures->keyBy('id');

        $activeSubscription = $tenant->subscriptions
            ->sortByDesc('starts_at')
            ->first();

        return [
            'id' => $tenant->id,
            'code' => $tenant->code,
            'name' => $tenant->name,
            'status' => $tenant->status,
            'industry' => $tenant->industry,
            'timezone' => $tenant->timezone,
            'billing_email' => $tenant->billing_email,
            'subscription' => $activeSubscription ? [
                'plan' => $activeSubscription->plan?->name,
                'status' => $activeSubscription->status,
                'renewal_at' => $activeSubscription->renewal_at?->toDateString(),
            ] : null,
            'users' => $tenant->users->map(fn (User $user) => $this->transformTenantUser($tenant, $user))->values(),
            'modules' => $tenant->modules->map(function (Module $module) use ($tenantFeatures) {
                return [
                    'id' => $module->id,
                    'key' => $module->key,
                    'name' => $module->name,
                    'category' => $module->category,
                    'is_core' => (bool) $module->is_core,
                    'has_mobile_app' => (bool) $module->has_mobile_app,
                    'status' => $module->pivot->status,
                    'seat_limit' => $module->pivot->seat_limit,
                    'settings' => $module->pivot->settings,
                    'activated_at' => $module->pivot->activated_at?->toDateTimeString(),
                    'deactivated_at' => $module->pivot->deactivated_at?->toDateTimeString(),
                    'features' => $module->features->map(function (ModuleFeature $feature) use ($tenantFeatures) {
                        $assigned = $tenantFeatures->get($feature->id);

                        return [
                            'id' => $feature->id,
                            'key' => $feature->key,
                            'name' => $feature->name,
                            'category' => $feature->category,
                            'is_default' => (bool) $feature->is_default,
                            'description' => $feature->description,
                            'status' => $assigned?->pivot->status ?? ($feature->is_default ? 'enabled' : 'disabled'),
                            'settings' => $assigned?->pivot->settings,
                            'enabled' => $assigned?->pivot->status === 'enabled' || ($feature->is_default && !$assigned),
                        ];
                    })->values(),
                ];
            })->values(),
        ];
    }

    protected function transformTenantUser(Tenant $tenant, User $user): array
    {
        $pivot = $user->pivot;
        $settings = $pivot?->settings;

        if (!is_array($settings)) {
            $decoded = is_string($settings) ? json_decode($settings, true) : null;
            $settings = is_array($decoded) ? $decoded : [];
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $pivot?->status,
            'is_primary' => (bool) ($pivot?->is_primary ?? false),
            'benefits' => $settings['benefits'] ?? [],
            'roles' => $user->roles->map(fn ($role) => $role->key)->values(),
        ];
    }
}
