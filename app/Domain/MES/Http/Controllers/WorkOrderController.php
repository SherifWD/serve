<?php

namespace App\Domain\MES\Http\Controllers;

use App\Domain\MES\Http\Requests\WorkOrderStoreRequest;
use App\Domain\MES\Http\Requests\WorkOrderUpdateRequest;
use App\Domain\MES\Http\Resources\WorkOrderResource;
use App\Domain\MES\Models\WorkOrder;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $workOrders = WorkOrder::query()
            ->with(['item', 'productionLine'])
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('production_line_id'), fn ($query, $lineId) => $query->where('production_line_id', $lineId))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return WorkOrderResource::collection($workOrders)->response();
    }

    public function store(WorkOrderStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $data = $request->validated();

        $workOrder = WorkOrder::create(array_merge($data, [
            'tenant_id' => $tenantId,
            'status' => $data['status'] ?? 'planned',
            'quantity_completed' => 0,
        ]));

        return WorkOrderResource::make($workOrder->load(['item', 'productionLine']))->response()->setStatusCode(201);
    }

    public function show(WorkOrder $workOrder): JsonResponse
    {
        $this->authorizeTenantResource($workOrder);

        return WorkOrderResource::make(
            $workOrder->load(['item', 'productionLine', 'events'])
        )->response();
    }

    public function update(WorkOrderUpdateRequest $request, WorkOrder $workOrder): JsonResponse
    {
        $this->authorizeTenantResource($workOrder);

        $workOrder->update($request->validated());

        return WorkOrderResource::make($workOrder->load(['item', 'productionLine', 'events']))->response();
    }

    public function destroy(WorkOrder $workOrder): JsonResponse
    {
        $this->authorizeTenantResource($workOrder);

        $workOrder->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(WorkOrder $workOrder): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        abort_if($workOrder->tenant_id !== $tenantId, 404);
    }
}

