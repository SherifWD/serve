<?php

namespace App\Domain\ERP\Http\Controllers;

use App\Domain\ERP\Http\Requests\DepartmentStoreRequest;
use App\Domain\ERP\Http\Requests\DepartmentUpdateRequest;
use App\Domain\ERP\Http\Resources\DepartmentResource;
use App\Domain\ERP\Models\Department;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $departments = Department::query()
            ->with('site')
            ->where('tenant_id', $tenantId)
            ->when($request->query('site_id'), fn ($query, $siteId) => $query->where('site_id', $siteId))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return DepartmentResource::collection($departments)->response();
    }

    public function store(DepartmentStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $department = Department::create(array_merge(
            $request->validated(),
            ['tenant_id' => $tenantId]
        ));

        return DepartmentResource::make($department->load('site'))->response()->setStatusCode(201);
    }

    public function show(Department $department): JsonResponse
    {
        $this->authorizeTenantResource($department);

        return DepartmentResource::make($department->load('site'))->response();
    }

    public function update(DepartmentUpdateRequest $request, Department $department): JsonResponse
    {
        $this->authorizeTenantResource($department);

        $department->update($request->validated());

        return DepartmentResource::make($department->load('site'))->response();
    }

    public function destroy(Department $department): JsonResponse
    {
        $this->authorizeTenantResource($department);

        $department->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Department $department): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        abort_if($department->tenant_id !== $tenantId, 404);
    }
}

