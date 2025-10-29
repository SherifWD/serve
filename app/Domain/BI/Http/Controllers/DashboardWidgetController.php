<?php

namespace App\Domain\BI\Http\Controllers;

use App\Domain\BI\Http\Requests\DashboardWidgetStoreRequest;
use App\Domain\BI\Http\Requests\DashboardWidgetUpdateRequest;
use App\Domain\BI\Http\Resources\DashboardWidgetResource;
use App\Domain\BI\Models\DashboardWidget;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardWidgetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $widgets = DashboardWidget::query()
            ->with('kpi')
            ->where('tenant_id', $tenantId)
            ->when($request->query('dashboard_id'), fn ($query, $dashboardId) => $query->where('dashboard_id', $dashboardId))
            ->orderBy('position')
            ->orderBy('id')
            ->paginate($request->integer('per_page', 20));

        return DashboardWidgetResource::collection($widgets)->response();
    }

    public function store(DashboardWidgetStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $data = $request->validated();

        if (!array_key_exists('position', $data) || $data['position'] === null) {
            $data['position'] = DashboardWidget::query()
                ->where('tenant_id', $tenantId)
                ->where('dashboard_id', $data['dashboard_id'])
                ->max('position') + 1;
        }

        $widget = DashboardWidget::create([
            ...$data,
            'tenant_id' => $tenantId,
        ]);

        return DashboardWidgetResource::make($widget->load('kpi'))->response()->setStatusCode(201);
    }

    public function show(DashboardWidget $dashboardWidget): JsonResponse
    {
        $this->authorizeTenantResource($dashboardWidget);

        return DashboardWidgetResource::make($dashboardWidget->load('kpi'))->response();
    }

    public function update(DashboardWidgetUpdateRequest $request, DashboardWidget $dashboardWidget): JsonResponse
    {
        $this->authorizeTenantResource($dashboardWidget);

        $dashboardWidget->update($request->validated());

        return DashboardWidgetResource::make($dashboardWidget->load('kpi'))->response();
    }

    public function destroy(DashboardWidget $dashboardWidget): JsonResponse
    {
        $this->authorizeTenantResource($dashboardWidget);
        $dashboardWidget->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(DashboardWidget $dashboardWidget): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($dashboardWidget->tenant_id !== $tenantId, 404);
    }
}
