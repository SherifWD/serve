<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiPermission
{
    /** @var array<string,array{view:string,manage:string}> */
    private array $resourcePermissions = [
        'branches' => ['view' => 'branches.view', 'manage' => 'branches.manage'],
        'categories' => ['view' => 'categories.view', 'manage' => 'categories.manage'],
        'products' => ['view' => 'products.view', 'manage' => 'products.manage'],
        'menus' => ['view' => 'menu.view', 'manage' => 'menu.manage'],
        'orders' => ['view' => 'orders.view', 'manage' => 'orders.manage'],
        'customers' => ['view' => 'customers.view', 'manage' => 'customers.manage'],
        'employees' => ['view' => 'employees.view', 'manage' => 'employees.manage'],
        'inventory-items' => ['view' => 'inventory.view', 'manage' => 'inventory.manage'],
        'inventory-transactions' => ['view' => 'inventory.view', 'manage' => 'inventory.manage'],
        'inventory-operations' => ['view' => 'inventory.view', 'manage' => 'inventory.manage'],
        'suppliers' => ['view' => 'suppliers.view', 'manage' => 'suppliers.manage'],
        'devices' => ['view' => 'branches.view', 'manage' => 'branches.manage'],
        'tables' => ['view' => 'tables.view', 'manage' => 'tables.manage'],
        'roles' => ['view' => 'roles.view', 'manage' => 'roles.view'],
        'users' => ['view' => 'users.view', 'manage' => 'users.manage'],
        'ingredients' => ['view' => 'ingredients.view', 'manage' => 'ingredients.manage'],
        'recipes' => ['view' => 'recipes.view', 'manage' => 'recipes.manage'],
        'fiscal-profiles' => ['view' => 'settings.view', 'manage' => 'settings.manage'],
        'branch-operation-settings' => ['view' => 'settings.view', 'manage' => 'settings.manage'],
        'receipts' => ['view' => 'orders.view', 'manage' => 'orders.manage'],
        'eta-submissions' => ['view' => 'orders.view', 'manage' => 'orders.manage'],
        'billing' => ['view' => 'settings.view', 'manage' => 'settings.manage'],
        'payment-providers' => ['view' => 'settings.view', 'manage' => 'settings.manage'],
        'print-jobs' => ['view' => 'orders.view', 'manage' => 'orders.manage'],
        'support' => ['view' => 'settings.view', 'manage' => 'settings.manage'],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return $next($request);
        }

        $segment = (string) $request->segment(2);
        $permission = $this->permissionFor($segment, $request->method());

        if ($permission && ! $user->hasPermission($permission) && ! $this->hasFallbackPermission($user, $permission)) {
            abort(403, 'You do not have permission to use this endpoint.');
        }

        return $next($request);
    }

    private function permissionFor(string $segment, string $method): ?string
    {
        if ($segment === 'dashboard') {
            return 'dashboard.view';
        }

        if ($segment === 'restaurants') {
            return in_array(strtoupper($method), ['GET', 'HEAD'], true)
                ? null
                : 'platform.restaurants.manage';
        }

        if (! isset($this->resourcePermissions[$segment])) {
            return null;
        }

        return in_array(strtoupper($method), ['GET', 'HEAD'], true)
            ? $this->resourcePermissions[$segment]['view']
            : $this->resourcePermissions[$segment]['manage'];
    }

    private function hasFallbackPermission(User $user, string $permission): bool
    {
        if ($permission === 'customers.view' && $user->isRestaurantOwner()) {
            return $user->permissionNames()->contains('orders.view');
        }

        return false;
    }
}
