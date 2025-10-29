<?php

namespace App\Domain\WMS\Http\Controllers;

use App\Domain\WMS\Http\Requests\TransferOrderStoreRequest;
use App\Domain\WMS\Http\Requests\TransferOrderUpdateRequest;
use App\Domain\WMS\Http\Resources\TransferOrderResource;
use App\Domain\WMS\Models\TransferOrder;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransferOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $orders = TransferOrder::query()
            ->with(['sourceBin', 'destinationBin', 'item'])
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('requested_at')
            ->paginate($request->integer('per_page', 20));

        return TransferOrderResource::collection($orders)->response();
    }

    public function store(TransferOrderStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $order = TransferOrder::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return TransferOrderResource::make($order->load(['sourceBin', 'destinationBin', 'item']))->response()->setStatusCode(201);
    }

    public function show(TransferOrder $transferOrder): JsonResponse
    {
        $this->authorizeTenantResource($transferOrder);

        return TransferOrderResource::make($transferOrder->load(['sourceBin', 'destinationBin', 'item']))->response();
    }

    public function update(TransferOrderUpdateRequest $request, TransferOrder $transferOrder): JsonResponse
    {
        $this->authorizeTenantResource($transferOrder);

        $transferOrder->update($request->validated());

        return TransferOrderResource::make($transferOrder->load(['sourceBin', 'destinationBin', 'item']))->response();
    }

    public function destroy(TransferOrder $transferOrder): JsonResponse
    {
        $this->authorizeTenantResource($transferOrder);
        $transferOrder->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(TransferOrder $transferOrder): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($transferOrder->tenant_id !== $tenantId, 404);
    }
}

