<?php

namespace App\Domain\CMMS\Http\Controllers;

use App\Domain\CMMS\Http\Requests\AssetStoreRequest;
use App\Domain\CMMS\Http\Requests\AssetUpdateRequest;
use App\Domain\CMMS\Http\Resources\AssetResource;
use App\Domain\CMMS\Models\Asset;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $assets = Asset::query()
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return AssetResource::collection($assets)->response();
    }

    public function store(AssetStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $asset = Asset::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return AssetResource::make($asset)->response()->setStatusCode(201);
    }

    public function show(Asset $asset): JsonResponse
    {
        $this->authorizeTenantResource($asset);

        return AssetResource::make($asset->load('maintenancePlans'))->response();
    }

    public function update(AssetUpdateRequest $request, Asset $asset): JsonResponse
    {
        $this->authorizeTenantResource($asset);

        $asset->update($request->validated());

        return AssetResource::make($asset)->response();
    }

    public function destroy(Asset $asset): JsonResponse
    {
        $this->authorizeTenantResource($asset);
        $asset->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Asset $asset): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($asset->tenant_id !== $tenantId, 404);
    }
}

