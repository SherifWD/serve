<?php

namespace App\Domain\CRM\Http\Controllers;

use App\Domain\CRM\Http\Requests\LeadStoreRequest;
use App\Domain\CRM\Http\Requests\LeadUpdateRequest;
use App\Domain\CRM\Http\Resources\LeadResource;
use App\Domain\CRM\Models\Lead;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $leads = Lead::query()
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return LeadResource::collection($leads)->response();
    }

    public function store(LeadStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $lead = Lead::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return LeadResource::make($lead)->response()->setStatusCode(201);
    }

    public function show(Lead $lead): JsonResponse
    {
        $this->authorizeTenantResource($lead);

        return LeadResource::make($lead)->response();
    }

    public function update(LeadUpdateRequest $request, Lead $lead): JsonResponse
    {
        $this->authorizeTenantResource($lead);

        $lead->update($request->validated());

        return LeadResource::make($lead)->response();
    }

    public function destroy(Lead $lead): JsonResponse
    {
        $this->authorizeTenantResource($lead);
        $lead->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Lead $lead): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($lead->tenant_id !== $tenantId, 404);
    }
}

