<?php

namespace App\Domain\QMS\Http\Controllers;

use App\Domain\QMS\Http\Requests\NonConformityStoreRequest;
use App\Domain\QMS\Http\Requests\NonConformityUpdateRequest;
use App\Domain\QMS\Http\Resources\NonConformityResource;
use App\Domain\QMS\Models\NonConformity;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NonConformityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $records = NonConformity::query()
            ->with('capaActions')
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('severity'), fn ($q, $severity) => $q->where('severity', $severity))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return NonConformityResource::collection($records)->response();
    }

    public function store(NonConformityStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $record = NonConformity::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return NonConformityResource::make($record)->response()->setStatusCode(201);
    }

    public function show(NonConformity $nonConformity): JsonResponse
    {
        $this->authorizeTenantResource($nonConformity);

        return NonConformityResource::make($nonConformity->load('capaActions'))->response();
    }

    public function update(NonConformityUpdateRequest $request, NonConformity $nonConformity): JsonResponse
    {
        $this->authorizeTenantResource($nonConformity);

        $nonConformity->update($request->validated());

        return NonConformityResource::make($nonConformity->load('capaActions'))->response();
    }

    public function destroy(NonConformity $nonConformity): JsonResponse
    {
        $this->authorizeTenantResource($nonConformity);
        $nonConformity->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(NonConformity $nonConformity): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($nonConformity->tenant_id !== $tenantId, 404);
    }
}

