<?php

namespace App\Domain\BI\Http\Controllers;

use App\Domain\BI\Http\Requests\DataSnapshotStoreRequest;
use App\Domain\BI\Http\Requests\DataSnapshotUpdateRequest;
use App\Domain\BI\Http\Resources\DataSnapshotResource;
use App\Domain\BI\Models\DataSnapshot;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DataSnapshotController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $snapshots = DataSnapshot::query()
            ->with('kpi')
            ->where('tenant_id', $tenantId)
            ->when($request->query('kpi_id'), fn ($query, $kpiId) => $query->where('kpi_id', $kpiId))
            ->when($request->query('from_date'), fn ($query, $date) => $query->whereDate('snapshot_date', '>=', $date))
            ->when($request->query('to_date'), fn ($query, $date) => $query->whereDate('snapshot_date', '<=', $date))
            ->orderByDesc('snapshot_date')
            ->paginate($request->integer('per_page', 20));

        return DataSnapshotResource::collection($snapshots)->response();
    }

    public function store(DataSnapshotStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $snapshot = DataSnapshot::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return DataSnapshotResource::make($snapshot->load('kpi'))->response()->setStatusCode(201);
    }

    public function show(DataSnapshot $dataSnapshot): JsonResponse
    {
        $this->authorizeTenantResource($dataSnapshot);

        return DataSnapshotResource::make($dataSnapshot->load('kpi'))->response();
    }

    public function update(DataSnapshotUpdateRequest $request, DataSnapshot $dataSnapshot): JsonResponse
    {
        $this->authorizeTenantResource($dataSnapshot);

        $dataSnapshot->update($request->validated());

        return DataSnapshotResource::make($dataSnapshot->load('kpi'))->response();
    }

    public function destroy(DataSnapshot $dataSnapshot): JsonResponse
    {
        $this->authorizeTenantResource($dataSnapshot);
        $dataSnapshot->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(DataSnapshot $dataSnapshot): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($dataSnapshot->tenant_id !== $tenantId, 404);
    }
}
