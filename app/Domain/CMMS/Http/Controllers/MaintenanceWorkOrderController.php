<?php

namespace App\Domain\CMMS\Http\Controllers;

use App\Domain\CMMS\Http\Requests\MaintenanceWorkOrderStoreRequest;
use App\Domain\CMMS\Http\Requests\MaintenanceWorkOrderUpdateRequest;
use App\Domain\CMMS\Http\Resources\MaintenanceWorkOrderResource;
use App\Domain\CMMS\Models\MaintenanceWorkOrder;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceWorkOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $orders = MaintenanceWorkOrder::query()
            ->with(['asset', 'plan'])
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('scheduled_date')
            ->paginate($request->integer('per_page', 20));

        return MaintenanceWorkOrderResource::collection($orders)->response();
    }

    public function store(MaintenanceWorkOrderStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $order = MaintenanceWorkOrder::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return MaintenanceWorkOrderResource::make($order->load(['asset', 'plan']))->response()->setStatusCode(201);
    }

    public function show(MaintenanceWorkOrder $workOrder): JsonResponse
    {
        $this->authorizeTenantResource($workOrder);

        return MaintenanceWorkOrderResource::make(
            $workOrder->load(['asset', 'plan', 'logs'])
        )->response();
    }

    public function update(MaintenanceWorkOrderUpdateRequest $request, MaintenanceWorkOrder $workOrder): JsonResponse
    {
        $this->authorizeTenantResource($workOrder);

        $workOrder->update($request->validated());

        return MaintenanceWorkOrderResource::make($workOrder->load(['asset', 'plan', 'logs']))->response();
    }

    public function destroy(MaintenanceWorkOrder $workOrder): JsonResponse
    {
        $this->authorizeTenantResource($workOrder);
        $workOrder->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource($model): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($model->tenant_id !== $tenantId, 404);
    }
}
