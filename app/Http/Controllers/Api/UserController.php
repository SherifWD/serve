<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Role;
use App\Models\Type;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $viewer = $request->user();

        $query = User::query()
            ->with([
                'restaurant:id,name,kind',
                'branch:id,name,restaurant_id',
                'roles:id,name',
                'types:id,name',
            ])
            ->orderBy('name');

        if ($viewer->isPlatformAdmin()) {
            $query
                ->when($request->filled('restaurant_id'), fn ($q) => $q->where('restaurant_id', $request->integer('restaurant_id')))
                ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->integer('branch_id')))
                ->when($request->filled('role'), fn ($q) => $q->where('role', $request->string('role')->toString()));
        } elseif ($viewer->restaurant_id) {
            $query->where('restaurant_id', $viewer->restaurant_id);
        } else {
            $query->where('branch_id', $viewer->branch_id);
        }

        return $query->get()->map(fn (User $user) => $this->serializeUser($user));
    }

    public function store(Request $request)
    {
        $viewer = $request->user();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => ['required', 'string', 'exists:roles,name'],
            'restaurant_id' => 'nullable|integer|exists:restaurants,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'types' => 'nullable|array',
            'types.*' => 'string|exists:types,name',
            'password' => 'nullable|string|min:6',
        ]);

        $branch = $data['branch_id'] ?? null
            ? Branch::query()->findOrFail($data['branch_id'])
            : null;

        [$restaurantId, $branchId] = $this->resolveAssignments(
            viewer: $viewer,
            role: $data['role'],
            restaurantId: $data['restaurant_id'] ?? null,
            branch: $branch,
        );

        $password = $data['password'] ?? Str::password(10);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'restaurant_id' => $restaurantId,
            'branch_id' => $branchId,
            'password' => Hash::make($password),
        ]);

        $this->syncRoleAndTypes($user, $data['role'], $data['types'] ?? []);

        return response()->json([
            'user' => $this->serializeUser($user->fresh(['restaurant', 'branch', 'roles', 'types'])),
            'temporary_password' => $data['password'] ?? $password,
        ], 201);
    }

    public function show(Request $request, User $user)
    {
        $viewer = $request->user();

        if (!$viewer->isPlatformAdmin()) {
            if (
                !$viewer->restaurant_id
                || (int) $viewer->restaurant_id !== (int) $user->restaurant_id
            ) {
                abort(403, 'You cannot view this user.');
            }
        }

        return $this->serializeUser($user->loadMissing(['restaurant', 'branch', 'roles', 'types']));
    }

    public function update(Request $request, User $user)
    {
        $viewer = $request->user();
        $this->assertCanManageUser($viewer, $user);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['sometimes', 'required', 'string', 'exists:roles,name'],
            'restaurant_id' => 'nullable|integer|exists:restaurants,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'types' => 'nullable|array',
            'types.*' => 'string|exists:types,name',
            'password' => 'nullable|string|min:6',
        ]);

        $nextRole = $data['role'] ?? $user->role;
        $branch = array_key_exists('branch_id', $data) && $data['branch_id']
            ? Branch::query()->findOrFail($data['branch_id'])
            : (array_key_exists('branch_id', $data) ? null : $user->branch);

        [$restaurantId, $branchId] = $this->resolveAssignments(
            viewer: $viewer,
            role: $nextRole,
            restaurantId: $data['restaurant_id'] ?? $user->restaurant_id,
            branch: $branch,
        );

        $user->fill([
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
            'role' => $nextRole,
            'restaurant_id' => $restaurantId,
            'branch_id' => $branchId,
        ]);

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        if (array_key_exists('role', $data) || array_key_exists('types', $data)) {
            $this->syncRoleAndTypes($user, $nextRole, $data['types'] ?? $user->types->pluck('name')->all());
        }

        return $this->serializeUser($user->fresh(['restaurant', 'branch', 'roles', 'types']));
    }

    public function destroy(Request $request, User $user)
    {
        $this->assertCanManageUser($request->user(), $user);
        $user->delete();

        return response()->json(['message' => 'User deleted']);
    }

    private function resolveAssignments(User $viewer, string $role, ?int $restaurantId, ?Branch $branch): array
    {
        if (!$viewer->isPlatformAdmin()) {
            if (in_array($role, ['admin', 'owner'], true)) {
                throw ValidationException::withMessages([
                    'role' => 'Only platform admins can assign admin or owner roles.',
                ]);
            }

            $restaurantId = $viewer->restaurant_id;

            if ($branch && (int) $branch->restaurant_id !== (int) $viewer->restaurant_id) {
                throw ValidationException::withMessages([
                    'branch_id' => 'This branch does not belong to your restaurant.',
                ]);
            }
        }

        if ($branch) {
            $restaurantId = $branch->restaurant_id;
        }

        if ($role === 'admin') {
            return [null, null];
        }

        if ($role === 'owner') {
            if (!$restaurantId) {
                throw ValidationException::withMessages([
                    'restaurant_id' => 'Restaurant is required for owner accounts.',
                ]);
            }

            return [$restaurantId, null];
        }

        if (!$branch) {
            throw ValidationException::withMessages([
                'branch_id' => 'Branch is required for this user role.',
            ]);
        }

        if (!$restaurantId) {
            throw ValidationException::withMessages([
                'restaurant_id' => 'Restaurant is required for this user.',
            ]);
        }

        return [$restaurantId, $branch->id];
    }

    private function syncRoleAndTypes(User $user, string $roleName, array $typeNames): void
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName]);
        $user->roles()->sync([$role->id]);

        if (empty($typeNames)) {
            $user->types()->sync([]);
            return;
        }

        $typeIds = Type::query()
            ->whereIn('name', $typeNames)
            ->pluck('id')
            ->all();

        $user->types()->sync($typeIds);
    }

    private function assertCanManageUser(User $viewer, User $target): void
    {
        if ($viewer->isPlatformAdmin()) {
            return;
        }

        if ($target->restaurant_id && (int) $target->restaurant_id === (int) $viewer->restaurant_id) {
            if (in_array($target->role, ['admin', 'owner'], true)) {
                abort(403, 'Owners cannot manage admin or owner accounts.');
            }

            return;
        }

        abort(403, 'You cannot manage this user.');
    }

    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'restaurant_id' => $user->restaurant_id,
            'restaurant' => $user->restaurant ? [
                'id' => $user->restaurant->id,
                'name' => $user->restaurant->name,
                'kind' => $user->restaurant->kind,
            ] : null,
            'branch_id' => $user->branch_id,
            'branch' => $user->branch ? [
                'id' => $user->branch->id,
                'name' => $user->branch->name,
                'restaurant_id' => $user->branch->restaurant_id,
            ] : null,
            'roles' => $user->roles->pluck('name')->values(),
            'types' => $user->types->pluck('name')->values(),
            'permissions' => $user->permissionNames()->values(),
        ];
    }
}
