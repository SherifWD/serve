<?php

namespace App\Domain\MES\Http\Controllers;

use App\Domain\MES\Http\Requests\ProductionLineStoreRequest;
use App\Domain\MES\Http\Requests\ProductionLineUpdateRequest;
use App\Domain\MES\Http\Resources\ProductionLineResource;
use App\Domain\MES\Models\ProductionLine;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductionLineController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $productionLines = ProductionLine::query()
            ->with('site')
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return ProductionLineResource::collection($productionLines)->response();
    }

    public function store(ProductionLineStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $productionLine = ProductionLine::create(array_merge(
            $request->validated(),
            ['tenant_id' => $tenantId]
        ));

        return ProductionLineResource::make($productionLine->load('site'))->response()->setStatusCode(201);
    }

    public function show(ProductionLine $productionLine): JsonResponse
    {
        $this->authorizeTenantResource($productionLine);

        return ProductionLineResource::make($productionLine->load('site'))->response();
    }

    public function update(ProductionLineUpdateRequest $request, ProductionLine $productionLine): JsonResponse
    {
        $this->authorizeTenantResource($productionLine);

        $productionLine->update($request->validated());

        return ProductionLineResource::make($productionLine->load('site'))->response();
    }

    public function destroy(ProductionLine $productionLine): JsonResponse
    {
        $this->authorizeTenantResource($productionLine);

        $productionLine->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(ProductionLine $productionLine): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        abort_if($productionLine->tenant_id !== $tenantId, 404);
    }
}

