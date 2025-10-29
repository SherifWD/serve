<?php

namespace App\Domain\HSE\Http\Controllers;

use App\Domain\HSE\Http\Requests\IncidentStoreRequest;
use App\Domain\HSE\Http\Requests\IncidentUpdateRequest;
use App\Domain\HSE\Http\Resources\IncidentResource;
use App\Domain\HSE\Models\Incident;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $incidents = Incident::query()
            ->withCount('actions')
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('incident_date')
            ->paginate($request->integer('per_page', 20));

        return IncidentResource::collection($incidents)->response();
    }

    public function store(IncidentStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $incident = Incident::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return IncidentResource::make($incident)->response()->setStatusCode(201);
    }

    public function show(Incident $incident): JsonResponse
    {
        $this->authorizeTenantResource($incident);

        return IncidentResource::make($incident->load('actions'))->response();
    }

    public function update(IncidentUpdateRequest $request, Incident $incident): JsonResponse
    {
        $this->authorizeTenantResource($incident);

        $incident->update($request->validated());

        return IncidentResource::make($incident->load('actions'))->response();
    }

    public function destroy(Incident $incident): JsonResponse
    {
        $this->authorizeTenantResource($incident);
        $incident->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Incident $incident): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($incident->tenant_id !== $tenantId, 404);
    }
}
