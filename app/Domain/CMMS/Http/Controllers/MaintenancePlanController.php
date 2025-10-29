<?php

namespace App\Domain\CMMS\Http\Controllers;

use App\Domain\CMMS\Http\Requests\MaintenancePlanStoreRequest;
use App\Domain\CMMS\Http\Requests\MaintenancePlanUpdateRequest;
use App\Domain\CMMS\Http\Resources\MaintenancePlanResource;
use App\Domain\CMMS\Models\MaintenancePlan;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenancePlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $plans = MaintenancePlan::query()
            ->with('asset')
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return MaintenancePlanResource::collection($plans)->response();
    }

    public function store(MaintenancePlanStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $plan = MaintenancePlan::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return MaintenancePlanResource::make($plan->load('asset'))->response()->setStatusCode(201);
    }

    public function show(MaintenancePlan $maintenancePlan): JsonResponse
    {
        $this->authorizeTenantResource($maintenancePlan);

        return MaintenancePlanResource::make($maintenancePlan->load('asset'))->response();
    }

    public function update(MaintenancePlanUpdateRequest $request, MaintenancePlan $maintenancePlan): JsonResponse
    {
        $this->authorizeTenantResource($maintenancePlan);

        $maintenancePlan->update($request->validated());

        return MaintenancePlanResource::make($maintenancePlan->load('asset'))->response();
    }

    public function destroy(MaintenancePlan $maintenancePlan): JsonResponse
    {
        $this->authorizeTenantResource($maintenancePlan);
        $maintenancePlan->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(MaintenancePlan $maintenancePlan): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($maintenancePlan->tenant_id !== $tenantId, 404);
    }
}

