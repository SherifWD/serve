<?php

namespace App\Domain\BI\Http\Controllers;

use App\Domain\BI\Http\Requests\DashboardStoreRequest;
use App\Domain\BI\Http\Requests\DashboardUpdateRequest;
use App\Domain\BI\Http\Resources\DashboardResource;
use App\Domain\BI\Models\Dashboard;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $dashboards = Dashboard::query()
            ->withCount('widgets')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('is_default')
            ->orderBy('title')
            ->paginate($request->integer('per_page', 20));

        return DashboardResource::collection($dashboards)->response();
    }

    public function store(DashboardStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $dashboard = Dashboard::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        if ($dashboard->is_default) {
            Dashboard::query()
                ->where('tenant_id', $tenantId)
                ->where('id', '!=', $dashboard->id)
                ->update(['is_default' => false]);
        }

        return DashboardResource::make($dashboard)->response()->setStatusCode(201);
    }

    public function show(Dashboard $dashboard): JsonResponse
    {
        $this->authorizeTenantResource($dashboard);

        return DashboardResource::make($dashboard->load('widgets.kpi'))->response();
    }

    public function update(DashboardUpdateRequest $request, Dashboard $dashboard): JsonResponse
    {
        $this->authorizeTenantResource($dashboard);

        $dashboard->update($request->validated());

        if ($request->filled('is_default') && $dashboard->is_default) {
            Dashboard::query()
                ->where('tenant_id', $dashboard->tenant_id)
                ->where('id', '!=', $dashboard->id)
                ->update(['is_default' => false]);
        }

        return DashboardResource::make($dashboard)->response();
    }

    public function destroy(Dashboard $dashboard): JsonResponse
    {
        $this->authorizeTenantResource($dashboard);
        $dashboard->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Dashboard $dashboard): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($dashboard->tenant_id !== $tenantId, 404);
    }
}
