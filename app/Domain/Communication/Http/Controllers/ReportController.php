<?php

namespace App\Domain\Communication\Http\Controllers;

use App\Domain\Communication\Models\Announcement;
use App\Domain\Communication\Models\WorkflowAction;
use App\Domain\Communication\Models\WorkflowRequest;
use App\Http\Controllers\Controller;
use App\Support\Reporting\CsvExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReportController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $announcements = $this->filteredAnnouncements($tenantId, $request);
        $workflowRequests = $this->filteredWorkflowRequests($tenantId, $request);
        $workflowActions = $this->filteredWorkflowActions($tenantId, $workflowRequests->pluck('id'), $request);

        $announcementStatus = $announcements
            ->groupBy('status')
            ->map(fn (Collection $group, string $status) => ['status' => $status, 'count' => $group->count()])
            ->values();

        $announcementPriority = $announcements
            ->groupBy('priority')
            ->map(fn (Collection $group, string $priority) => ['priority' => $priority, 'count' => $group->count()])
            ->values();

        $workflowStatus = $workflowRequests
            ->groupBy('status')
            ->map(fn (Collection $group, string $status) => ['status' => $status, 'count' => $group->count()])
            ->values();

        $workflowPriority = $workflowRequests
            ->groupBy('priority')
            ->map(fn (Collection $group, string $priority) => ['priority' => $priority, 'count' => $group->count()])
            ->values();

        $actionsByType = $workflowActions
            ->groupBy('action')
            ->map(fn (Collection $group, string $action) => ['action' => $action, 'count' => $group->count()])
            ->values();

        $slaBreaches = $workflowRequests->filter(function (WorkflowRequest $requestModel) {
            if (!$requestModel->due_at || !$requestModel->requested_at) {
                return false;
            }

            return now()->greaterThan($requestModel->due_at) && $requestModel->status !== 'completed';
        })->count();

        $workflowDetails = $workflowRequests
            ->sortByDesc(fn (WorkflowRequest $requestModel) => $requestModel->requested_at ?? $requestModel->created_at)
            ->take(20)
            ->map(fn (WorkflowRequest $requestModel) => [
                'id' => $requestModel->id,
                'reference' => $requestModel->reference,
                'title' => $requestModel->title,
                'status' => $requestModel->status,
                'priority' => $requestModel->priority,
                'requested_at' => $requestModel->requested_at?->toDateString(),
                'due_at' => $requestModel->due_at?->toDateString(),
            ])
            ->values();

        return response()->json([
            'filters' => $request->only(['status', 'priority', 'request_type']),
            'announcements' => [
                'total' => $announcements->count(),
                'status_breakdown' => $announcementStatus,
                'priority_breakdown' => $announcementPriority,
                'scheduled' => $announcements->where('status', 'scheduled')->count(),
                'published' => $announcements->where('status', 'published')->count(),
            ],
            'workflow_requests' => [
                'total' => $workflowRequests->count(),
                'status_breakdown' => $workflowStatus,
                'priority_breakdown' => $workflowPriority,
                'sla_breaches' => $slaBreaches,
                'details' => $workflowDetails,
            ],
            'workflow_actions' => [
                'total' => $workflowActions->count(),
                'by_type' => $actionsByType,
            ],
        ]);
    }

    public function export(Request $request)
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $workflowRequests = $this->filteredWorkflowRequests($tenantId, $request);
        $workflowActions = $this->filteredWorkflowActions($tenantId, $workflowRequests->pluck('id'), $request);

        $headers = [
            'reference',
            'request_type',
            'title',
            'status',
            'priority',
            'requested_at',
            'due_at',
            'actions_taken',
        ];

        $rows = $workflowRequests->map(function (WorkflowRequest $workflowRequest) use ($workflowActions) {
            $actions = $workflowActions
                ->where('workflow_request_id', $workflowRequest->id)
                ->groupBy('action')
                ->map(fn (Collection $group, string $action) => sprintf('%s (%d)', $action, $group->count()))
                ->values()
                ->implode('; ');

            return [
                'reference' => $workflowRequest->reference,
                'request_type' => $workflowRequest->request_type,
                'title' => $workflowRequest->title,
                'status' => $workflowRequest->status,
                'priority' => $workflowRequest->priority,
                'requested_at' => optional($workflowRequest->requested_at)->toDateString(),
                'due_at' => optional($workflowRequest->due_at)->toDateString(),
                'actions_taken' => $actions,
            ];
        });

        return CsvExporter::stream(
            sprintf('communication-report-%s', now()->format('Ymd_His')),
            $headers,
            $rows
        );
    }

    protected function filteredAnnouncements(int $tenantId, Request $request): Collection
    {
        return Announcement::query()
            ->where('tenant_id', $tenantId)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->string('priority')))
            ->when($request->filled('publish_from'), fn ($query) => $query->whereDate('publish_at', '>=', $request->date('publish_from')))
            ->when($request->filled('publish_to'), fn ($query) => $query->whereDate('publish_at', '<=', $request->date('publish_to')))
            ->get();
    }

    protected function filteredWorkflowRequests(int $tenantId, Request $request): Collection
    {
        return WorkflowRequest::query()
            ->where('tenant_id', $tenantId)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->string('priority')))
            ->when($request->filled('request_type'), fn ($query) => $query->where('request_type', $request->string('request_type')))
            ->when($request->filled('requested_from'), fn ($query) => $query->whereDate('requested_at', '>=', $request->date('requested_from')))
            ->when($request->filled('requested_to'), fn ($query) => $query->whereDate('requested_at', '<=', $request->date('requested_to')))
            ->get();
    }

    protected function filteredWorkflowActions(int $tenantId, Collection $workflowRequestIds, Request $request): Collection
    {
        if ($workflowRequestIds->isEmpty()) {
            return collect();
        }

        return WorkflowAction::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('workflow_request_id', $workflowRequestIds)
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->get();
    }
}
