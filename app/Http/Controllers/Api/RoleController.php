<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(
            $request->user()->isPlatformAdmin() || $request->user()->isRestaurantOwner(),
            403,
            'You cannot view role permissions.'
        );

        $roles = Role::query()
            ->with('permissions:id,name')
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'users_count' => $role->users_count,
                'permissions' => $role->permissions->pluck('name')->values(),
            ]);

        $permissions = Permission::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Permission $permission) => [
                'id' => $permission->id,
                'name' => $permission->name,
                'group' => $this->permissionGroup($permission->name),
            ]);

        return [
            'roles' => $roles,
            'permissions' => $permissions,
        ];
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isPlatformAdmin(), 403, 'Only platform admins can create roles.');

        $data = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::query()->create(['name' => $data['name']]);
        $this->syncPermissions($role, $data['permissions'] ?? []);

        return $role->load('permissions:id,name');
    }

    public function show(Request $request, string $id)
    {
        abort_unless(
            $request->user()->isPlatformAdmin() || $request->user()->isRestaurantOwner(),
            403,
            'You cannot view roles.'
        );

        return Role::query()->with('permissions:id,name')->findOrFail($id);
    }

    public function update(Request $request, string $id)
    {
        abort_unless($request->user()->isPlatformAdmin(), 403, 'Only platform admins can update roles.');

        $role = Role::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        if (array_key_exists('name', $data)) {
            $role->name = $data['name'];
            $role->save();
        }

        if (array_key_exists('permissions', $data)) {
            $this->syncPermissions($role, $data['permissions']);
        }

        return $role->load('permissions:id,name');
    }

    public function destroy(Request $request, string $id)
    {
        abort_unless($request->user()->isPlatformAdmin(), 403, 'Only platform admins can delete roles.');

        $role = Role::query()->findOrFail($id);
        $role->delete();

        return response()->json(['message' => 'Role deleted']);
    }

    private function syncPermissions(Role $role, array $permissionNames): void
    {
        $ids = Permission::query()
            ->whereIn('name', $permissionNames)
            ->pluck('id')
            ->all();

        $role->permissions()->sync($ids);
    }

    private function permissionGroup(string $name): string
    {
        if (str_starts_with($name, 'platform.')) {
            return 'Platform';
        }
        if (str_starts_with($name, 'dashboard.')) {
            return 'Dashboard';
        }
        if (str_starts_with($name, 'branches.')) {
            return 'Branches';
        }
        if (str_starts_with($name, 'users.') || str_starts_with($name, 'roles.') || str_starts_with($name, 'employees.')) {
            return 'People';
        }
        if (str_starts_with($name, 'orders.') || str_starts_with($name, 'cashier.') || str_starts_with($name, 'kds.')) {
            return 'Operations';
        }
        if (str_starts_with($name, 'menu.') || str_starts_with($name, 'products.') || str_starts_with($name, 'categories.')) {
            return 'Menu';
        }
        if (str_starts_with($name, 'inventory.') || str_starts_with($name, 'suppliers.') || str_starts_with($name, 'recipes.')) {
            return 'Inventory';
        }

        return 'General';
    }
}
