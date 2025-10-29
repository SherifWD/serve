<?php

namespace App\Domain\HRMS\Http\Controllers;

use App\Domain\HRMS\Http\Requests\EmploymentContractStoreRequest;
use App\Domain\HRMS\Http\Requests\EmploymentContractUpdateRequest;
use App\Domain\HRMS\Http\Resources\EmploymentContractResource;
use App\Domain\HRMS\Models\EmploymentContract;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmploymentContractController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $contracts = EmploymentContract::query()
            ->where('tenant_id', $tenantId)
            ->when($request->query('worker_id'), fn ($q, $workerId) => $q->where('worker_id', $workerId))
            ->orderByDesc('start_date')
            ->paginate($request->integer('per_page', 20));

        return EmploymentContractResource::collection($contracts)->response();
    }

    public function store(EmploymentContractStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $contract = EmploymentContract::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return EmploymentContractResource::make($contract)->response()->setStatusCode(201);
    }

    public function show(EmploymentContract $employmentContract): JsonResponse
    {
        $this->authorizeTenantResource($employmentContract);

        return EmploymentContractResource::make($employmentContract)->response();
    }

    public function update(EmploymentContractUpdateRequest $request, EmploymentContract $employmentContract): JsonResponse
    {
        $this->authorizeTenantResource($employmentContract);

        $employmentContract->update($request->validated());

        return EmploymentContractResource::make($employmentContract)->response();
    }

    public function destroy(EmploymentContract $employmentContract): JsonResponse
    {
        $this->authorizeTenantResource($employmentContract);
        $employmentContract->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(EmploymentContract $employmentContract): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($employmentContract->tenant_id !== $tenantId, 404);
    }
}

