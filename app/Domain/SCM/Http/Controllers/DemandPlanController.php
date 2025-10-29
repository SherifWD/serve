<?php

namespace App\Domain\SCM\Http\Controllers;

use App\Domain\SCM\Http\Requests\DemandPlanStoreRequest;
use App\Domain\SCM\Http\Requests\DemandPlanUpdateRequest;
use App\Domain\SCM\Http\Resources\DemandPlanResource;
use App\Domain\SCM\Models\DemandPlan;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DemandPlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $plans = DemandPlan::query()
            ->with('item')
            ->where('tenant_id', $tenantId)
            ->when($request->query('item_id'), fn ($q, $itemId) => $q->where('item_id', $itemId))
            ->when($request->query('period'), fn ($q, $period) => $q->where('period', $period))
            ->orderByDesc('period')
            ->paginate($request->integer('per_page', 20));

        return DemandPlanResource::collection($plans)->response();
    }

    public function store(DemandPlanStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $plan = DemandPlan::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return DemandPlanResource::make($plan->load('item'))->response()->setStatusCode(201);
    }

    public function show(DemandPlan $demandPlan): JsonResponse
    {
        $this->authorizeTenantResource($demandPlan);

        return DemandPlanResource::make($demandPlan->load('item'))->response();
    }

    public function update(DemandPlanUpdateRequest $request, DemandPlan $demandPlan): JsonResponse
    {
        $this->authorizeTenantResource($demandPlan);

        $demandPlan->update($request->validated());

        return DemandPlanResource::make($demandPlan->load('item'))->response();
    }

    public function destroy(DemandPlan $demandPlan): JsonResponse
    {
        $this->authorizeTenantResource($demandPlan);

        $demandPlan->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(DemandPlan $demandPlan): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($demandPlan->tenant_id !== $tenantId, 404);
    }
}

