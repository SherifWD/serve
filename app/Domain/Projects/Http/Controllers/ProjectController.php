<?php

namespace App\Domain\Projects\Http\Controllers;

use App\Domain\Projects\Http\Requests\ProjectStoreRequest;
use App\Domain\Projects\Http\Requests\ProjectUpdateRequest;
use App\Domain\Projects\Http\Resources\ProjectResource;
use App\Domain\Projects\Models\Project;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $projects = Project::query()
            ->with(['owner'])
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('stage'), fn ($query, $stage) => $query->where('stage', $stage))
            ->when($request->query('search'), function ($query, $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return ProjectResource::collection($projects)->response();
    }

    public function store(ProjectStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $project = Project::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return ProjectResource::make($project->load('owner'))->response()->setStatusCode(201);
    }

    public function show(Project $project): JsonResponse
    {
        $this->authorizeTenantResource($project);

        $project->load([
            'owner',
            'tasks.assignee',
            'tasks.dependency',
            'changeRequests.approvals.approver',
        ]);

        return ProjectResource::make($project)->response();
    }

    public function update(ProjectUpdateRequest $request, Project $project): JsonResponse
    {
        $this->authorizeTenantResource($project);

        $project->update($request->validated());

        return ProjectResource::make($project->load('owner'))->response();
    }

    public function destroy(Project $project): JsonResponse
    {
        $this->authorizeTenantResource($project);
        $project->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Project $project): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($project->tenant_id !== $tenantId, 404);
    }
}
