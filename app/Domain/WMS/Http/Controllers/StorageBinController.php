<?php

namespace App\Domain\WMS\Http\Controllers;

use App\Domain\WMS\Http\Requests\StorageBinStoreRequest;
use App\Domain\WMS\Http\Requests\StorageBinUpdateRequest;
use App\Domain\WMS\Http\Resources\StorageBinResource;
use App\Domain\WMS\Models\StorageBin;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorageBinController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $bins = StorageBin::query()
            ->with('warehouse')
            ->where('tenant_id', $tenantId)
            ->when($request->query('warehouse_id'), fn ($q, $warehouseId) => $q->where('warehouse_id', $warehouseId))
            ->orderBy('code')
            ->paginate($request->integer('per_page', 20));

        return StorageBinResource::collection($bins)->response();
    }

    public function store(StorageBinStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $bin = StorageBin::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return StorageBinResource::make($bin->load('warehouse'))->response()->setStatusCode(201);
    }

    public function show(StorageBin $storageBin): JsonResponse
    {
        $this->authorizeTenantResource($storageBin);

        return StorageBinResource::make($storageBin->load('warehouse'))->response();
    }

    public function update(StorageBinUpdateRequest $request, StorageBin $storageBin): JsonResponse
    {
        $this->authorizeTenantResource($storageBin);

        $storageBin->update($request->validated());

        return StorageBinResource::make($storageBin->load('warehouse'))->response();
    }

    public function destroy(StorageBin $storageBin): JsonResponse
    {
        $this->authorizeTenantResource($storageBin);
        $storageBin->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(StorageBin $storageBin): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($storageBin->tenant_id !== $tenantId, 404);
    }
}

