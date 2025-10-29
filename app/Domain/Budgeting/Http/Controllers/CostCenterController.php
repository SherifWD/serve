<?php

namespace App\Domain\Budgeting\Http\Controllers;

use App\Domain\Budgeting\Http\Requests\CostCenterStoreRequest;
use App\Domain\Budgeting\Http\Requests\CostCenterUpdateRequest;
use App\Domain\Budgeting\Http\Resources\CostCenterResource;
use App\Domain\Budgeting\Models\CostCenter;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CostCenterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $costCenters = CostCenter::query()
            ->where('tenant_id', $tenantId)
            ->withCount(['budgets', 'actuals'])
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('search'), function ($query, $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%");
                });
            })
            ->orderBy('code')
            ->paginate($request->integer('per_page', 20));

        return CostCenterResource::collection($costCenters)->response();
    }

    public function store(CostCenterStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $costCenter = CostCenter::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return CostCenterResource::make($costCenter)->response()->setStatusCode(201);
    }

    public function show(CostCenter $costCenter): JsonResponse
    {
        $this->authorizeTenantResource($costCenter);

        $costCenter->load([
            'budgets' => fn ($query) => $query->latest('period')->limit(6),
        ]);

        return CostCenterResource::make($costCenter)->response();
    }

    public function update(CostCenterUpdateRequest $request, CostCenter $costCenter): JsonResponse
    {
        $this->authorizeTenantResource($costCenter);

        $costCenter->update($request->validated());

        return CostCenterResource::make($costCenter)->response();
    }

    public function destroy(CostCenter $costCenter): JsonResponse
    {
        $this->authorizeTenantResource($costCenter);
        $costCenter->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(CostCenter $costCenter): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($costCenter->tenant_id !== $tenantId, 404);
    }
}
