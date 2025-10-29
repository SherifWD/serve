<?php

namespace App\Domain\Projects\Http\Controllers;

use App\Domain\Projects\Http\Requests\ChangeRequestStoreRequest;
use App\Domain\Projects\Http\Requests\ChangeRequestUpdateRequest;
use App\Domain\Projects\Http\Resources\ChangeRequestResource;
use App\Domain\Projects\Models\ChangeRequest;
use App\Domain\Projects\Models\Project;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChangeRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $changes = ChangeRequest::query()
            ->with(['project', 'approvals'])
            ->where('tenant_id', $tenantId)
            ->when($request->query('project_id'), fn ($query, $projectId) => $query->where('project_id', $projectId))
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('change_type'), fn ($query, $type) => $query->where('change_type', $type))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return ChangeRequestResource::collection($changes)->response();
    }

    public function store(ChangeRequestStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $data = $request->validated();

        Project::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $data['project_id'])
            ->firstOrFail();

        $change = ChangeRequest::create([
            ...$data,
            'tenant_id' => $tenantId,
        ]);

        return ChangeRequestResource::make($change->load(['project']))->response()->setStatusCode(201);
    }

    public function show(ChangeRequest $changeRequest): JsonResponse
    {
        $this->authorizeTenantResource($changeRequest);

        $changeRequest->load(['project', 'approvals.approver', 'requester']);

        return ChangeRequestResource::make($changeRequest)->response();
    }

    public function update(ChangeRequestUpdateRequest $request, ChangeRequest $changeRequest): JsonResponse
    {
        $this->authorizeTenantResource($changeRequest);
        $data = $request->validated();
        $tenantId = app('tenant.context')->ensureTenant()->id;

        if (isset($data['project_id'])) {
            Project::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $data['project_id'])
                ->firstOrFail();
        }

        $changeRequest->update($data);

        return ChangeRequestResource::make($changeRequest->load(['project']))->response();
    }

    public function destroy(ChangeRequest $changeRequest): JsonResponse
    {
        $this->authorizeTenantResource($changeRequest);
        $changeRequest->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(ChangeRequest $changeRequest): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($changeRequest->tenant_id !== $tenantId, 404);
    }
}
