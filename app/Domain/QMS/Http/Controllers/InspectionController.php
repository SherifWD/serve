<?php

namespace App\Domain\QMS\Http\Controllers;

use App\Domain\QMS\Http\Requests\InspectionStoreRequest;
use App\Domain\QMS\Http\Requests\InspectionUpdateRequest;
use App\Domain\QMS\Http\Resources\InspectionResource;
use App\Domain\QMS\Models\Inspection;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InspectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $inspections = Inspection::query()
            ->with(['plan', 'item', 'nonConformities'])
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('inspection_plan_id'), fn ($q, $planId) => $q->where('inspection_plan_id', $planId))
            ->orderByDesc('inspected_at')
            ->paginate($request->integer('per_page', 20));

        return InspectionResource::collection($inspections)->response();
    }

    public function store(InspectionStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $inspection = Inspection::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return InspectionResource::make($inspection->load(['plan', 'item']))->response()->setStatusCode(201);
    }

    public function show(Inspection $inspection): JsonResponse
    {
        $this->authorizeTenantResource($inspection);

        return InspectionResource::make($inspection->load(['plan', 'item', 'nonConformities']))->response();
    }

    public function update(InspectionUpdateRequest $request, Inspection $inspection): JsonResponse
    {
        $this->authorizeTenantResource($inspection);

        $inspection->update($request->validated());

        return InspectionResource::make($inspection->load(['plan', 'item', 'nonConformities']))->response();
    }

    public function destroy(Inspection $inspection): JsonResponse
    {
        $this->authorizeTenantResource($inspection);
        $inspection->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Inspection $inspection): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($inspection->tenant_id !== $tenantId, 404);
    }
}

