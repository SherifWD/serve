<?php

namespace App\Domain\WMS\Http\Controllers;

use App\Domain\WMS\Http\Requests\WarehouseStoreRequest;
use App\Domain\WMS\Http\Requests\WarehouseUpdateRequest;
use App\Domain\WMS\Http\Resources\WarehouseResource;
use App\Domain\WMS\Models\Warehouse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $warehouses = Warehouse::query()
            ->with('storageBins')
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return WarehouseResource::collection($warehouses)->response();
    }

    public function store(WarehouseStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $warehouse = Warehouse::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return WarehouseResource::make($warehouse)->response()->setStatusCode(201);
    }

    public function show(Warehouse $warehouse): JsonResponse
    {
        $this->authorizeTenantResource($warehouse);

        return WarehouseResource::make($warehouse->load('storageBins'))->response();
    }

    public function update(WarehouseUpdateRequest $request, Warehouse $warehouse): JsonResponse
    {
        $this->authorizeTenantResource($warehouse);

        $warehouse->update($request->validated());

        return WarehouseResource::make($warehouse->load('storageBins'))->response();
    }

    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $this->authorizeTenantResource($warehouse);
        $warehouse->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Warehouse $warehouse): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($warehouse->tenant_id !== $tenantId, 404);
    }
}

