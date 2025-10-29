<?php

namespace App\Platform\Tenancy\Http\Middleware;

use App\Platform\Tenancy\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantContext = app('tenant.context');

        if ($tenantContext->getTenant()) {
            return $next($request);
        }

        $tenantIdentifier = $request->header('X-Tenant')
            ?? $request->route('tenant')
            ?? $request->input('tenant_id');

        if (!$tenantIdentifier && $request->user()) {
            $tenant = $request->user()->tenants()->first();
            if ($tenant) {
                $tenantContext->setTenant($tenant);

                return $next($request);
            }
        }

        if (!$tenantIdentifier) {
            abort(Response::HTTP_BAD_REQUEST, 'Tenant context missing.');
        }

        $tenant = Tenant::query()
            ->where('uid', $tenantIdentifier)
            ->orWhere('code', $tenantIdentifier)
            ->orWhere('id', $tenantIdentifier)
            ->first();

        if (!$tenant) {
            abort(Response::HTTP_NOT_FOUND, 'Tenant not found.');
        }

        if ($request->user()) {
            $hasAccess = $request->user()->tenants()->where('tenants.id', $tenant->id)->exists();

            if (!$hasAccess) {
                abort(Response::HTTP_FORBIDDEN, 'You do not have access to this tenant.');
            }
        }

        $tenantContext->setTenant($tenant);

        return $next($request);
    }
}
