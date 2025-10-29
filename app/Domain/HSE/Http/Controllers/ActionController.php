<?php

namespace App\Domain\HSE\Http\Controllers;

use App\Domain\HSE\Http\Requests\ActionStoreRequest;
use App\Domain\HSE\Http\Requests\ActionUpdateRequest;
use App\Domain\HSE\Http\Resources\ActionResource;
use App\Domain\HSE\Models\Action;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $actions = Action::query()
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('incident_id'), fn ($query, $incidentId) => $query->where('incident_id', $incidentId))
            ->when($request->query('audit_id'), fn ($query, $auditId) => $query->where('audit_id', $auditId))
            ->orderBy('due_date')
            ->paginate($request->integer('per_page', 20));

        return ActionResource::collection($actions)->response();
    }

    public function store(ActionStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $action = Action::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return ActionResource::make($action)->response()->setStatusCode(201);
    }

    public function show(Action $action): JsonResponse
    {
        $this->authorizeTenantResource($action);

        return ActionResource::make($action)->response();
    }

    public function update(ActionUpdateRequest $request, Action $action): JsonResponse
    {
        $this->authorizeTenantResource($action);

        $action->update($request->validated());

        return ActionResource::make($action)->response();
    }

    public function destroy(Action $action): JsonResponse
    {
        $this->authorizeTenantResource($action);
        $action->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Action $action): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($action->tenant_id !== $tenantId, 404);
    }
}
