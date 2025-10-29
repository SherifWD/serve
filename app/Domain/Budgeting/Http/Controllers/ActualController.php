<?php

namespace App\Domain\Budgeting\Http\Controllers;

use App\Domain\Budgeting\Http\Requests\ActualStoreRequest;
use App\Domain\Budgeting\Http\Requests\ActualUpdateRequest;
use App\Domain\Budgeting\Http\Resources\ActualResource;
use App\Domain\Budgeting\Models\Actual;
use App\Domain\Budgeting\Models\CostCenter;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActualController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $actuals = Actual::query()
            ->with('costCenter')
            ->where('tenant_id', $tenantId)
            ->when($request->query('cost_center_id'), fn ($query, $id) => $query->where('cost_center_id', $id))
            ->when($request->query('fiscal_year'), fn ($query, $year) => $query->where('fiscal_year', $year))
            ->orderByDesc('fiscal_year')
            ->orderByDesc('period')
            ->paginate($request->integer('per_page', 25));

        return ActualResource::collection($actuals)->response();
    }

    public function store(ActualStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $data = $request->validated();

        CostCenter::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $data['cost_center_id'])
            ->firstOrFail();

        $actual = Actual::create([
            ...$data,
            'tenant_id' => $tenantId,
        ])->load('costCenter');

        return ActualResource::make($actual)->response()->setStatusCode(201);
    }

    public function show(Actual $actual): JsonResponse
    {
        $this->authorizeTenantResource($actual);

        return ActualResource::make($actual->load('costCenter'))->response();
    }

    public function update(ActualUpdateRequest $request, Actual $actual): JsonResponse
    {
        $this->authorizeTenantResource($actual);
        $data = $request->validated();
        $tenantId = app('tenant.context')->ensureTenant()->id;

        if (isset($data['cost_center_id'])) {
            CostCenter::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $data['cost_center_id'])
                ->firstOrFail();
        }

        $actual->update($data);

        return ActualResource::make($actual->load('costCenter'))->response();
    }

    public function destroy(Actual $actual): JsonResponse
    {
        $this->authorizeTenantResource($actual);
        $actual->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Actual $actual): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($actual->tenant_id !== $tenantId, 404);
    }
}
