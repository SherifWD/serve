<?php

namespace App\Domain\PLM\Http\Controllers;

use App\Domain\PLM\Http\Requests\ProductDesignStoreRequest;
use App\Domain\PLM\Http\Requests\ProductDesignUpdateRequest;
use App\Domain\PLM\Http\Resources\ProductDesignResource;
use App\Domain\PLM\Models\ProductDesign;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductDesignController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $designs = ProductDesign::query()
            ->with('item')
            ->where('tenant_id', $tenantId)
            ->when($request->query('lifecycle_state'), fn ($query, $state) => $query->where('lifecycle_state', $state))
            ->when($request->query('search'), fn ($query, $search) => $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return ProductDesignResource::collection($designs)->response();
    }

    public function store(ProductDesignStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $design = ProductDesign::create(array_merge(
            $request->validated(),
            [
                'tenant_id' => $tenantId,
                'lifecycle_state' => $request->input('lifecycle_state', 'in_design'),
            ]
        ));

        return ProductDesignResource::make($design->load('item'))->response()->setStatusCode(201);
    }

    public function show(ProductDesign $productDesign): JsonResponse
    {
        $this->authorizeTenantResource($productDesign);

        return ProductDesignResource::make(
            $productDesign->load(['item', 'changes', 'documents'])
        )->response();
    }

    public function update(ProductDesignUpdateRequest $request, ProductDesign $productDesign): JsonResponse
    {
        $this->authorizeTenantResource($productDesign);

        $productDesign->update($request->validated());

        return ProductDesignResource::make($productDesign->load(['item', 'changes', 'documents']))->response();
    }

    public function destroy(ProductDesign $productDesign): JsonResponse
    {
        $this->authorizeTenantResource($productDesign);

        $productDesign->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(ProductDesign $productDesign): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        abort_if($productDesign->tenant_id !== $tenantId, 404);
    }
}

