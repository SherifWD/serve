<?php

namespace App\Domain\Projects\Http\Controllers;

use App\Domain\Projects\Models\ChangeRequest;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectTask;
use App\Http\Controllers\Controller;
use App\Support\Reporting\CsvExporter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReportController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $projects = $this->filteredProjects($tenantId, $request);
        $projectIds = $projects->pluck('id');

        $tasks = $this->filteredTasks($tenantId, $projectIds, $request);
        $changeRequests = $this->filteredChangeRequests($tenantId, $projectIds, $request);

        $projectStatus = $projects
            ->groupBy('status')
            ->map(fn (Collection $group, string $status) => ['status' => $status, 'count' => $group->count()])
            ->values();

        $taskStatus = $tasks
            ->groupBy('status')
            ->map(fn (Collection $group, string $status) => ['status' => $status, 'count' => $group->count()])
            ->values();

        $taskPriority = $tasks
            ->groupBy('priority')
            ->map(fn (Collection $group, string $priority) => ['priority' => $priority, 'count' => $group->count()])
            ->values();

        $changeStatus = $changeRequests
            ->groupBy('status')
            ->map(fn (Collection $group, string $status) => ['status' => $status, 'count' => $group->count()])
            ->values();

        $timeline = $projects->map(function (Project $project) use ($tasks) {
            $completion = $tasks
                ->where('project_id', $project->id)
                ->when(
                    true,
                    fn ($collection) => $collection->avg('progress')
                );

            return [
                'project_id' => $project->id,
                'code' => $project->code,
                'name' => $project->name,
                'status' => $project->status,
                'start_date' => optional($project->start_date)->toDateString(),
                'due_date' => optional($project->due_date)->toDateString(),
                'days_remaining' => $project->due_date
                    ? now()->diffInDays(Carbon::parse($project->due_date), false)
                    : null,
                'average_progress' => round((float) ($completion ?? 0), 1),
            ];
        });

        return response()->json([
            'filters' => $request->only(['status', 'stage', 'owner_id']),
            'projects' => [
                'total' => $projects->count(),
                'status_breakdown' => $projectStatus,
                'average_budget' => number_format((float) $projects->avg('budget_amount'), 2, '.', ''),
            ],
            'tasks' => [
                'total' => $tasks->count(),
                'status_breakdown' => $taskStatus,
                'priority_breakdown' => $taskPriority,
                'average_progress' => round((float) $tasks->avg('progress'), 2),
            ],
            'change_requests' => [
                'total' => $changeRequests->count(),
                'status_breakdown' => $changeStatus,
            ],
            'timeline' => $timeline,
        ]);
    }

    public function export(Request $request)
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $projects = $this->filteredProjects($tenantId, $request);
        $projectIds = $projects->pluck('id');
        $tasks = $this->filteredTasks($tenantId, $projectIds, $request);
        $changeRequests = $this->filteredChangeRequests($tenantId, $projectIds, $request);

        $headers = [
            'code',
            'name',
            'status',
            'stage',
            'start_date',
            'due_date',
            'budget_amount',
            'task_count',
            'avg_progress',
            'open_changes',
            'approved_changes',
        ];

        $rows = $projects->map(function (Project $project) use ($tasks, $changeRequests) {
            $projectTasks = $tasks->where('project_id', $project->id);
            $projectChangeRequests = $changeRequests->where('project_id', $project->id);

            return [
                'code' => $project->code,
                'name' => $project->name,
                'status' => $project->status,
                'stage' => $project->stage,
                'start_date' => optional($project->start_date)->toDateString(),
                'due_date' => optional($project->due_date)->toDateString(),
                'budget_amount' => number_format((float) ($project->budget_amount ?? 0), 2, '.', ''),
                'task_count' => $projectTasks->count(),
                'avg_progress' => round((float) $projectTasks->avg('progress'), 2),
                'open_changes' => $projectChangeRequests->whereIn('status', ['draft', 'submitted', 'in_review'])->count(),
                'approved_changes' => $projectChangeRequests->where('status', 'approved')->count(),
            ];
        });

        return CsvExporter::stream(
            sprintf('projects-report-%s', now()->format('Ymd_His')),
            $headers,
            $rows
        );
    }

    protected function filteredProjects(int $tenantId, Request $request): Collection
    {
        return Project::query()
            ->where('tenant_id', $tenantId)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('stage'), fn ($query) => $query->where('stage', $request->string('stage')))
            ->when($request->filled('owner_id'), fn ($query) => $query->where('owner_id', $request->integer('owner_id')))
            ->get();
    }

    protected function filteredTasks(int $tenantId, Collection $projectIds, Request $request): Collection
    {
        if ($projectIds->isEmpty()) {
            return collect();
        }

        return ProjectTask::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('project_id', $projectIds)
            ->when($request->filled('task_status'), fn ($query) => $query->where('status', $request->string('task_status')))
            ->when($request->filled('task_priority'), fn ($query) => $query->where('priority', $request->string('task_priority')))
            ->get();
    }

    protected function filteredChangeRequests(int $tenantId, Collection $projectIds, Request $request): Collection
    {
        if ($projectIds->isEmpty()) {
            return collect();
        }

        return ChangeRequest::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('project_id', $projectIds)
            ->when($request->filled('change_status'), fn ($query) => $query->where('status', $request->string('change_status')))
            ->get();
    }
}
