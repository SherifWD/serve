<?php

namespace App\Domain\BI\Http\Controllers;

use App\Domain\BI\Http\Requests\KpiStoreRequest;
use App\Domain\BI\Http\Requests\KpiUpdateRequest;
use App\Domain\BI\Http\Resources\KpiResource;
use App\Domain\BI\Models\Kpi;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KpiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $kpis = Kpi::query()
            ->where('tenant_id', $tenantId)
            ->when($request->query('category'), fn ($query, $category) => $query->where('category', $category))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return KpiResource::collection($kpis)->response();
    }

    public function store(KpiStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $kpi = Kpi::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return KpiResource::make($kpi)->response()->setStatusCode(201);
    }

    public function show(Kpi $kpi): JsonResponse
    {
        $this->authorizeTenantResource($kpi);

        return KpiResource::make($kpi->load('snapshots'))->response();
    }

    public function update(KpiUpdateRequest $request, Kpi $kpi): JsonResponse
    {
        $this->authorizeTenantResource($kpi);

        $kpi->update($request->validated());

        return KpiResource::make($kpi)->response();
    }

    public function destroy(Kpi $kpi): JsonResponse
    {
        $this->authorizeTenantResource($kpi);
        $kpi->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Kpi $kpi): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($kpi->tenant_id !== $tenantId, 404);
    }
}
