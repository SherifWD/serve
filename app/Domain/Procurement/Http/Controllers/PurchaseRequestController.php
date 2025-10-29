<?php

namespace App\Domain\Procurement\Http\Controllers;

use App\Domain\Procurement\Http\Requests\PurchaseRequestStoreRequest;
use App\Domain\Procurement\Http\Requests\PurchaseRequestUpdateRequest;
use App\Domain\Procurement\Http\Resources\PurchaseRequestResource;
use App\Domain\Procurement\Models\PurchaseRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $requests = PurchaseRequest::query()
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('department'), fn ($query, $department) => $query->where('department', $department))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return PurchaseRequestResource::collection($requests)->response();
    }

    public function store(PurchaseRequestStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $purchaseRequest = PurchaseRequest::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return PurchaseRequestResource::make($purchaseRequest)->response()->setStatusCode(201);
    }

    public function show(PurchaseRequest $purchaseRequest): JsonResponse
    {
        $this->authorizeTenantResource($purchaseRequest);

        return PurchaseRequestResource::make($purchaseRequest)->response();
    }

    public function update(PurchaseRequestUpdateRequest $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        $this->authorizeTenantResource($purchaseRequest);

        $purchaseRequest->update($request->validated());

        return PurchaseRequestResource::make($purchaseRequest)->response();
    }

    public function destroy(PurchaseRequest $purchaseRequest): JsonResponse
    {
        $this->authorizeTenantResource($purchaseRequest);
        $purchaseRequest->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(PurchaseRequest $purchaseRequest): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($purchaseRequest->tenant_id !== $tenantId, 404);
    }
}
