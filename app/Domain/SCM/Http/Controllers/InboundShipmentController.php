<?php

namespace App\Domain\SCM\Http\Controllers;

use App\Domain\SCM\Http\Requests\InboundShipmentStoreRequest;
use App\Domain\SCM\Http\Requests\InboundShipmentUpdateRequest;
use App\Domain\SCM\Http\Resources\InboundShipmentResource;
use App\Domain\SCM\Models\InboundShipment;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InboundShipmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $shipments = InboundShipment::query()
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('purchase_order_id'), fn ($q, $poId) => $q->where('purchase_order_id', $poId))
            ->orderByDesc('arrival_date')
            ->paginate($request->integer('per_page', 20));

        return InboundShipmentResource::collection($shipments)->response();
    }

    public function store(InboundShipmentStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $shipment = InboundShipment::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return InboundShipmentResource::make($shipment)->response()->setStatusCode(201);
    }

    public function show(InboundShipment $inboundShipment): JsonResponse
    {
        $this->authorizeTenantResource($inboundShipment);

        return InboundShipmentResource::make($inboundShipment)->response();
    }

    public function update(InboundShipmentUpdateRequest $request, InboundShipment $inboundShipment): JsonResponse
    {
        $this->authorizeTenantResource($inboundShipment);

        $inboundShipment->update($request->validated());

        return InboundShipmentResource::make($inboundShipment->refresh())->response();
    }

    public function destroy(InboundShipment $inboundShipment): JsonResponse
    {
        $this->authorizeTenantResource($inboundShipment);
        $inboundShipment->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(InboundShipment $inboundShipment): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($inboundShipment->tenant_id !== $tenantId, 404);
    }
}

