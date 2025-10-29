<?php

namespace App\Domain\ERP\Http\Controllers;

use App\Domain\ERP\Http\Requests\SiteStoreRequest;
use App\Domain\ERP\Http\Requests\SiteUpdateRequest;
use App\Domain\ERP\Http\Resources\SiteResource;
use App\Domain\ERP\Models\Site;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $sites = Site::query()
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return SiteResource::collection($sites)->response();
    }

    public function store(SiteStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $site = Site::create(array_merge(
            $request->validated(),
            ['tenant_id' => $tenantId]
        ));

        return SiteResource::make($site)->response()->setStatusCode(201);
    }

    public function show(Site $site): JsonResponse
    {
        $this->authorizeTenantResource($site);

        return SiteResource::make($site)->response();
    }

    public function update(SiteUpdateRequest $request, Site $site): JsonResponse
    {
        $this->authorizeTenantResource($site);

        $site->update($request->validated());

        return SiteResource::make($site->refresh())->response();
    }

    public function destroy(Site $site): JsonResponse
    {
        $this->authorizeTenantResource($site);

        $site->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Site $site): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        abort_if($site->tenant_id !== $tenantId, 404);
    }
}

