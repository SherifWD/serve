<?php

namespace App\Domain\CRM\Http\Controllers;

use App\Domain\CRM\Http\Requests\ServiceCaseStoreRequest;
use App\Domain\CRM\Http\Requests\ServiceCaseUpdateRequest;
use App\Domain\CRM\Http\Resources\ServiceCaseResource;
use App\Domain\CRM\Models\ServiceCase;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceCaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $cases = ServiceCase::query()
            ->with('account')
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return ServiceCaseResource::collection($cases)->response();
    }

    public function store(ServiceCaseStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $case = ServiceCase::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return ServiceCaseResource::make($case->load('account'))->response()->setStatusCode(201);
    }

    public function show(ServiceCase $serviceCase): JsonResponse
    {
        $this->authorizeTenantResource($serviceCase);

        return ServiceCaseResource::make($serviceCase->load('account'))->response();
    }

    public function update(ServiceCaseUpdateRequest $request, ServiceCase $serviceCase): JsonResponse
    {
        $this->authorizeTenantResource($serviceCase);

        $serviceCase->update($request->validated());

        return ServiceCaseResource::make($serviceCase->load('account'))->response();
    }

    public function destroy(ServiceCase $serviceCase): JsonResponse
    {
        $this->authorizeTenantResource($serviceCase);
        $serviceCase->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(ServiceCase $serviceCase): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($serviceCase->tenant_id !== $tenantId, 404);
    }
}

