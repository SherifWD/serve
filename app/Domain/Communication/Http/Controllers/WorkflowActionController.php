<?php

namespace App\Domain\Communication\Http\Controllers;

use App\Domain\Communication\Http\Requests\WorkflowActionStoreRequest;
use App\Domain\Communication\Http\Requests\WorkflowActionUpdateRequest;
use App\Domain\Communication\Http\Resources\WorkflowActionResource;
use App\Domain\Communication\Models\WorkflowAction;
use App\Domain\Communication\Models\WorkflowRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowActionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $actions = WorkflowAction::query()
            ->with(['workflowRequest', 'actor'])
            ->where('tenant_id', $tenantId)
            ->when($request->query('workflow_request_id'), fn ($query, $id) => $query->where('workflow_request_id', $id))
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('action'), fn ($query, $action) => $query->where('action', $action))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 25));

        return WorkflowActionResource::collection($actions)->response();
    }

    public function store(WorkflowActionStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $data = $request->validated();

        WorkflowRequest::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $data['workflow_request_id'])
            ->firstOrFail();

        $action = WorkflowAction::create([
            ...$data,
            'tenant_id' => $tenantId,
        ])->load(['workflowRequest', 'actor']);

        return WorkflowActionResource::make($action)->response()->setStatusCode(201);
    }

    public function show(WorkflowAction $workflowAction): JsonResponse
    {
        $this->authorizeTenantResource($workflowAction);

        return WorkflowActionResource::make($workflowAction->load(['workflowRequest', 'actor']))->response();
    }

    public function update(WorkflowActionUpdateRequest $request, WorkflowAction $workflowAction): JsonResponse
    {
        $this->authorizeTenantResource($workflowAction);
        $data = $request->validated();
        $tenantId = app('tenant.context')->ensureTenant()->id;

        if (isset($data['workflow_request_id'])) {
            WorkflowRequest::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $data['workflow_request_id'])
                ->firstOrFail();
        }

        $workflowAction->update($data);

        return WorkflowActionResource::make($workflowAction->load(['workflowRequest', 'actor']))->response();
    }

    public function destroy(WorkflowAction $workflowAction): JsonResponse
    {
        $this->authorizeTenantResource($workflowAction);
        $workflowAction->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(WorkflowAction $action): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($action->tenant_id !== $tenantId, 404);
    }
}
