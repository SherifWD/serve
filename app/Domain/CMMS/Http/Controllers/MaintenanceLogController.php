<?php

namespace App\Domain\CMMS\Http\Controllers;

use App\Domain\CMMS\Http\Requests\MaintenanceLogStoreRequest;
use App\Domain\CMMS\Http\Requests\MaintenanceLogUpdateRequest;
use App\Domain\CMMS\Http\Resources\MaintenanceLogResource;
use App\Domain\CMMS\Models\MaintenanceLog;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $logs = MaintenanceLog::query()
            ->with(['workOrder', 'user'])
            ->where('tenant_id', $tenantId)
            ->when($request->query('work_order_id'), fn ($q, $workOrderId) => $q->where('work_order_id', $workOrderId))
            ->orderByDesc('logged_at')
            ->paginate($request->integer('per_page', 20));

        return MaintenanceLogResource::collection($logs)->response();
    }

    public function store(MaintenanceLogStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $log = MaintenanceLog::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
            'logged_at' => $request->input('logged_at', now()),
        ]);

        return MaintenanceLogResource::make($log->load(['workOrder', 'user']))->response()->setStatusCode(201);
    }

    public function show(MaintenanceLog $maintenanceLog): JsonResponse
    {
        $this->authorizeTenantResource($maintenanceLog);

        return MaintenanceLogResource::make($maintenanceLog->load(['workOrder', 'user']))->response();
    }

    public function update(MaintenanceLogUpdateRequest $request, MaintenanceLog $maintenanceLog): JsonResponse
    {
        $this->authorizeTenantResource($maintenanceLog);

        $maintenanceLog->update($request->validated());

        return MaintenanceLogResource::make($maintenanceLog->load(['workOrder', 'user']))->response();
    }

    public function destroy(MaintenanceLog $maintenanceLog): JsonResponse
    {
        $this->authorizeTenantResource($maintenanceLog);
        $maintenanceLog->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(MaintenanceLog $maintenanceLog): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($maintenanceLog->tenant_id !== $tenantId, 404);
    }
}

