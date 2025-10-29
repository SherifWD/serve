<?php

namespace App\Domain\Budgeting\Http\Controllers;

use App\Domain\Budgeting\Http\Requests\BudgetStoreRequest;
use App\Domain\Budgeting\Http\Requests\BudgetUpdateRequest;
use App\Domain\Budgeting\Http\Resources\BudgetResource;
use App\Domain\Budgeting\Models\Budget;
use App\Domain\Budgeting\Models\CostCenter;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $budgets = Budget::query()
            ->with('costCenter')
            ->where('tenant_id', $tenantId)
            ->when($request->query('cost_center_id'), fn ($query, $id) => $query->where('cost_center_id', $id))
            ->when($request->query('fiscal_year'), fn ($query, $year) => $query->where('fiscal_year', $year))
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('fiscal_year')
            ->orderByDesc('period')
            ->paginate($request->integer('per_page', 20));

        return BudgetResource::collection($budgets)->response();
    }

    public function store(BudgetStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $data = $request->validated();

        CostCenter::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $data['cost_center_id'])
            ->firstOrFail();

        $budget = Budget::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'cost_center_id' => $data['cost_center_id'],
                'period' => $data['period'],
            ],
            [
                ...$data,
                'tenant_id' => $tenantId,
            ]
        );

        return BudgetResource::make($budget->load('costCenter'))->response()->setStatusCode(201);
    }

    public function show(Budget $budget): JsonResponse
    {
        $this->authorizeTenantResource($budget);

        return BudgetResource::make($budget->load('costCenter'))->response();
    }

    public function update(BudgetUpdateRequest $request, Budget $budget): JsonResponse
    {
        $this->authorizeTenantResource($budget);
        $data = $request->validated();
        $tenantId = app('tenant.context')->ensureTenant()->id;

        if (isset($data['cost_center_id'])) {
            CostCenter::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $data['cost_center_id'])
                ->firstOrFail();
        }

        $budget->update($data);

        return BudgetResource::make($budget->load('costCenter'))->response();
    }

    public function destroy(Budget $budget): JsonResponse
    {
        $this->authorizeTenantResource($budget);
        $budget->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Budget $budget): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($budget->tenant_id !== $tenantId, 404);
    }
}
