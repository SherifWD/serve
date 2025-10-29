<?php

namespace App\Domain\Communication\Http\Controllers;

use App\Domain\Communication\Http\Requests\WorkflowRequestStoreRequest;
use App\Domain\Communication\Http\Requests\WorkflowRequestUpdateRequest;
use App\Domain\Communication\Http\Resources\WorkflowRequestResource;
use App\Domain\Communication\Models\WorkflowRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $requests = WorkflowRequest::query()
            ->with(['requester', 'assignee'])
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('priority'), fn ($query, $priority) => $query->where('priority', $priority))
            ->when($request->query('request_type'), fn ($query, $type) => $query->where('request_type', $type))
            ->when($request->query('assignee_id'), fn ($query, $assignee) => $query->where('assignee_id', $assignee))
            ->when($request->query('search'), function ($query, $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 25));

        return WorkflowRequestResource::collection($requests)->response();
    }

    public function store(WorkflowRequestStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $workflow = WorkflowRequest::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return WorkflowRequestResource::make($workflow->load(['requester', 'assignee']))->response()->setStatusCode(201);
    }

    public function show(WorkflowRequest $workflowRequest): JsonResponse
    {
        $this->authorizeTenantResource($workflowRequest);

        $workflowRequest->load(['requester', 'assignee', 'actions.actor']);

        return WorkflowRequestResource::make($workflowRequest)->response();
    }

    public function update(WorkflowRequestUpdateRequest $request, WorkflowRequest $workflowRequest): JsonResponse
    {
        $this->authorizeTenantResource($workflowRequest);

        $workflowRequest->update($request->validated());

        return WorkflowRequestResource::make($workflowRequest->load(['requester', 'assignee']))->response();
    }

    public function destroy(WorkflowRequest $workflowRequest): JsonResponse
    {
        $this->authorizeTenantResource($workflowRequest);
        $workflowRequest->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(WorkflowRequest $workflowRequest): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($workflowRequest->tenant_id !== $tenantId, 404);
    }
}
