<?php

namespace App\Domain\WMS\Http\Controllers;

use App\Domain\WMS\Http\Requests\InventoryLotStoreRequest;
use App\Domain\WMS\Http\Requests\InventoryLotUpdateRequest;
use App\Domain\WMS\Http\Resources\InventoryLotResource;
use App\Domain\WMS\Models\InventoryLot;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryLotController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $lots = InventoryLot::query()
            ->with(['item', 'warehouse', 'storageBin'])
            ->where('tenant_id', $tenantId)
            ->when($request->query('warehouse_id'), fn ($q, $warehouseId) => $q->where('warehouse_id', $warehouseId))
            ->when($request->query('item_id'), fn ($q, $itemId) => $q->where('item_id', $itemId))
            ->orderByDesc('received_at')
            ->paginate($request->integer('per_page', 20));

        return InventoryLotResource::collection($lots)->response();
    }

    public function store(InventoryLotStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $lot = InventoryLot::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return InventoryLotResource::make($lot->load(['item', 'warehouse', 'storageBin']))->response()->setStatusCode(201);
    }

    public function show(InventoryLot $inventoryLot): JsonResponse
    {
        $this->authorizeTenantResource($inventoryLot);

        return InventoryLotResource::make($inventoryLot->load(['item', 'warehouse', 'storageBin']))->response();
    }

    public function update(InventoryLotUpdateRequest $request, InventoryLot $inventoryLot): JsonResponse
    {
        $this->authorizeTenantResource($inventoryLot);

        $inventoryLot->update($request->validated());

        return InventoryLotResource::make($inventoryLot->load(['item', 'warehouse', 'storageBin']))->response();
    }

    public function destroy(InventoryLot $inventoryLot): JsonResponse
    {
        $this->authorizeTenantResource($inventoryLot);

        $inventoryLot->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(InventoryLot $inventoryLot): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($inventoryLot->tenant_id !== $tenantId, 404);
    }
}

