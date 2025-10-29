<?php

namespace App\Domain\QMS\Http\Controllers;

use App\Domain\QMS\Http\Requests\InspectionPlanStoreRequest;
use App\Domain\QMS\Http\Requests\InspectionPlanUpdateRequest;
use App\Domain\QMS\Http\Resources\InspectionPlanResource;
use App\Domain\QMS\Models\InspectionPlan;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InspectionPlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $plans = InspectionPlan::query()
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return InspectionPlanResource::collection($plans)->response();
    }

    public function store(InspectionPlanStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $plan = InspectionPlan::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return InspectionPlanResource::make($plan)->response()->setStatusCode(201);
    }

    public function show(InspectionPlan $inspectionPlan): JsonResponse
    {
        $this->authorizeTenantResource($inspectionPlan);

        return InspectionPlanResource::make($inspectionPlan)->response();
    }

    public function update(InspectionPlanUpdateRequest $request, InspectionPlan $inspectionPlan): JsonResponse
    {
        $this->authorizeTenantResource($inspectionPlan);

        $inspectionPlan->update($request->validated());

        return InspectionPlanResource::make($inspectionPlan)->response();
    }

    public function destroy(InspectionPlan $inspectionPlan): JsonResponse
    {
        $this->authorizeTenantResource($inspectionPlan);
        $inspectionPlan->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(InspectionPlan $inspectionPlan): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($inspectionPlan->tenant_id !== $tenantId, 404);
    }
}

