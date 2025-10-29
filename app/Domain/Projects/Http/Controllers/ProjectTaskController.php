<?php

namespace App\Domain\Projects\Http\Controllers;

use App\Domain\Projects\Http\Requests\ProjectTaskStoreRequest;
use App\Domain\Projects\Http\Requests\ProjectTaskUpdateRequest;
use App\Domain\Projects\Http\Resources\ProjectTaskResource;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectTask;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectTaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $tasks = ProjectTask::query()
            ->with(['assignee', 'dependency'])
            ->where('tenant_id', $tenantId)
            ->when($request->query('project_id'), fn ($query, $projectId) => $query->where('project_id', $projectId))
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->orderBy('due_date')
            ->orderBy('priority', 'desc')
            ->paginate($request->integer('per_page', 25));

        return ProjectTaskResource::collection($tasks)->response();
    }

    public function store(ProjectTaskStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $data = $request->validated();

        Project::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $data['project_id'])
            ->firstOrFail();

        if (!empty($data['depends_on_task_id'])) {
            ProjectTask::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $data['depends_on_task_id'])
                ->firstOrFail();
        }

        $task = ProjectTask::create([
            ...$data,
            'tenant_id' => $tenantId,
        ])->load(['assignee', 'dependency']);

        return ProjectTaskResource::make($task)->response()->setStatusCode(201);
    }

    public function show(ProjectTask $projectTask): JsonResponse
    {
        $this->authorizeTenantResource($projectTask);

        return ProjectTaskResource::make($projectTask->load(['assignee', 'dependency']))->response();
    }

    public function update(ProjectTaskUpdateRequest $request, ProjectTask $projectTask): JsonResponse
    {
        $this->authorizeTenantResource($projectTask);
        $data = $request->validated();
        $tenantId = app('tenant.context')->ensureTenant()->id;

        if (isset($data['project_id'])) {
            Project::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $data['project_id'])
                ->firstOrFail();
        }

        if (array_key_exists('depends_on_task_id', $data) && $data['depends_on_task_id']) {
            ProjectTask::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $data['depends_on_task_id'])
                ->firstOrFail();
        }

        $projectTask->update($data);

        return ProjectTaskResource::make($projectTask->load(['assignee', 'dependency']))->response();
    }

    public function destroy(ProjectTask $projectTask): JsonResponse
    {
        $this->authorizeTenantResource($projectTask);
        $projectTask->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(ProjectTask $task): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($task->tenant_id !== $tenantId, 404);
    }
}
