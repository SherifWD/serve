<?php

namespace App\Domain\CRM\Http\Controllers;

use App\Domain\CRM\Http\Requests\OpportunityStoreRequest;
use App\Domain\CRM\Http\Requests\OpportunityUpdateRequest;
use App\Domain\CRM\Http\Resources\OpportunityResource;
use App\Domain\CRM\Models\Opportunity;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpportunityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $opportunities = Opportunity::query()
            ->with('account')
            ->where('tenant_id', $tenantId)
            ->when($request->query('stage'), fn ($q, $stage) => $q->where('stage', $stage))
            ->orderByDesc('close_date')
            ->paginate($request->integer('per_page', 20));

        return OpportunityResource::collection($opportunities)->response();
    }

    public function store(OpportunityStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $opportunity = Opportunity::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return OpportunityResource::make($opportunity->load('account'))->response()->setStatusCode(201);
    }

    public function show(Opportunity $opportunity): JsonResponse
    {
        $this->authorizeTenantResource($opportunity);

        return OpportunityResource::make($opportunity->load('account'))->response();
    }

    public function update(OpportunityUpdateRequest $request, Opportunity $opportunity): JsonResponse
    {
        $this->authorizeTenantResource($opportunity);

        $opportunity->update($request->validated());

        return OpportunityResource::make($opportunity->load('account'))->response();
    }

    public function destroy(Opportunity $opportunity): JsonResponse
    {
        $this->authorizeTenantResource($opportunity);
        $opportunity->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Opportunity $opportunity): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($opportunity->tenant_id !== $tenantId, 404);
    }
}

