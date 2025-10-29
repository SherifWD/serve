<?php

namespace App\Domain\QMS\Http\Controllers;

use App\Domain\QMS\Http\Requests\AuditStoreRequest;
use App\Domain\QMS\Http\Requests\AuditUpdateRequest;
use App\Domain\QMS\Http\Resources\AuditResource;
use App\Domain\QMS\Models\Audit;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $audits = Audit::query()
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('scheduled_date')
            ->paginate($request->integer('per_page', 20));

        return AuditResource::collection($audits)->response();
    }

    public function store(AuditStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $audit = Audit::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return AuditResource::make($audit)->response()->setStatusCode(201);
    }

    public function show(Audit $audit): JsonResponse
    {
        $this->authorizeTenantResource($audit);

        return AuditResource::make($audit)->response();
    }

    public function update(AuditUpdateRequest $request, Audit $audit): JsonResponse
    {
        $this->authorizeTenantResource($audit);

        $audit->update($request->validated());

        return AuditResource::make($audit)->response();
    }

    public function destroy(Audit $audit): JsonResponse
    {
        $this->authorizeTenantResource($audit);
        $audit->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Audit $audit): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($audit->tenant_id !== $tenantId, 404);
    }
}

