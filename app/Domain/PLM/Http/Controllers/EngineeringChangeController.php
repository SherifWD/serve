<?php

namespace App\Domain\PLM\Http\Controllers;

use App\Domain\PLM\Http\Requests\EngineeringChangeStoreRequest;
use App\Domain\PLM\Http\Requests\EngineeringChangeUpdateRequest;
use App\Domain\PLM\Http\Resources\EngineeringChangeResource;
use App\Domain\PLM\Models\EngineeringChange;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EngineeringChangeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $changes = EngineeringChange::query()
            ->where('tenant_id', $tenantId)
            ->when($request->query('product_design_id'), fn ($query, $designId) => $query->where('product_design_id', $designId))
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return EngineeringChangeResource::collection($changes)->response();
    }

    public function store(EngineeringChangeStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $data = $request->validated();

        $change = EngineeringChange::create(array_merge($data, [
            'tenant_id' => $tenantId,
            'status' => $data['status'] ?? 'draft',
            'requested_by' => $request->user()?->id,
        ]));

        return EngineeringChangeResource::make($change)->response()->setStatusCode(201);
    }

    public function show(EngineeringChange $engineeringChange): JsonResponse
    {
        $this->authorizeTenantResource($engineeringChange);

        return EngineeringChangeResource::make($engineeringChange)->response();
    }

    public function update(EngineeringChangeUpdateRequest $request, EngineeringChange $engineeringChange): JsonResponse
    {
        $this->authorizeTenantResource($engineeringChange);

        $engineeringChange->update($request->validated());

        return EngineeringChangeResource::make($engineeringChange)->response();
    }

    public function destroy(EngineeringChange $engineeringChange): JsonResponse
    {
        $this->authorizeTenantResource($engineeringChange);

        $engineeringChange->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(EngineeringChange $engineeringChange): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        abort_if($engineeringChange->tenant_id !== $tenantId, 404);
    }
}

