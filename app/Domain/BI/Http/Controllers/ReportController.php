<?php

namespace App\Domain\BI\Http\Controllers;

use App\Domain\BI\Models\Dashboard;
use App\Domain\BI\Models\DataSnapshot;
use App\Domain\BI\Models\Kpi;
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

        $kpis = $this->filteredKpis($tenantId, $request);
        $kpiIds = $kpis->pluck('id');

        $snapshots = $this->filteredSnapshots($tenantId, $kpiIds, $request);
        $dashboards = Dashboard::query()
            ->withCount('widgets')
            ->where('tenant_id', $tenantId)
            ->get();

        $kpiByCategory = $kpis
            ->groupBy('category')
            ->map(fn (Collection $group, ?string $category) => [
                'category' => $category ?? 'uncategorised',
                'count' => $group->count(),
            ])
            ->values();

        $trends = $snapshots
            ->groupBy(fn (DataSnapshot $snapshot) => $snapshot->snapshot_date->format('Y-m-d'))
            ->map(function (Collection $group, string $date) {
                return [
                    'date' => $date,
                    'average_value' => (float) $group->avg('value'),
                    'min_value' => (float) $group->min('value'),
                    'max_value' => (float) $group->max('value'),
                ];
            })
            ->values()
            ->sortBy('date')
            ->values();

        $kpiTargetPerformance = $kpis->map(function (Kpi $kpi) use ($snapshots) {
            $values = $snapshots->where('kpi_id', $kpi->id)->pluck('value');
            $latest = $values->last();
            $target = data_get($kpi->config, 'target');

            return [
                'kpi_id' => $kpi->id,
                'code' => $kpi->code,
                'name' => $kpi->name,
                'target' => $target,
                'latest_value' => $latest,
                'status' => $target === null
                    ? 'no_target'
                    : ($latest >= $target ? 'on_track' : 'at_risk'),
            ];
        });

        return response()->json([
            'filters' => $request->only(['category', 'dashboard_id', 'from', 'to']),
            'kpis' => [
                'total' => $kpis->count(),
                'by_category' => $kpiByCategory,
                'target_performance' => $kpiTargetPerformance,
            ],
            'dashboards' => [
                'total' => $dashboards->count(),
                'with_default' => $dashboards->where('is_default', true)->count(),
                'widget_counts' => $dashboards->map(fn (Dashboard $dashboard) => [
                    'dashboard_id' => $dashboard->id,
                    'title' => $dashboard->title,
                    'widgets' => $dashboard->widgets_count,
                ]),
            ],
            'trends' => $trends,
        ]);
    }

    public function export(Request $request)
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $kpis = $this->filteredKpis($tenantId, $request)->keyBy('id');
        $snapshots = $this->filteredSnapshots($tenantId, $kpis->keys(), $request);

        $headers = [
            'kpi_code',
            'kpi_name',
            'category',
            'snapshot_date',
            'value',
            'target',
            'variance',
        ];

        $rows = $snapshots->map(function (DataSnapshot $snapshot) use ($kpis) {
            $kpi = $kpis->get($snapshot->kpi_id);
            $target = data_get($kpi?->config, 'target');
            $variance = $target !== null ? $snapshot->value - $target : null;

            return [
                'kpi_code' => $kpi?->code,
                'kpi_name' => $kpi?->name,
                'category' => $kpi?->category,
                'snapshot_date' => $snapshot->snapshot_date->toDateString(),
                'value' => $snapshot->value,
                'target' => $target,
                'variance' => $variance,
            ];
        });

        return CsvExporter::stream(
            sprintf('bi-report-%s', now()->format('Ymd_His')),
            $headers,
            $rows
        );
    }

    protected function filteredKpis(int $tenantId, Request $request): Collection
    {
        return Kpi::query()
            ->where('tenant_id', $tenantId)
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))
            ->get();
    }

    protected function filteredSnapshots(int $tenantId, Collection $kpiIds, Request $request): Collection
    {
        if ($kpiIds->isEmpty()) {
            return collect();
        }

        return DataSnapshot::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('kpi_id', $kpiIds)
            ->when($request->filled('from'), fn ($query) => $query->whereDate('snapshot_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('snapshot_date', '<=', $request->date('to')))
            ->orderBy('snapshot_date')
            ->get();
    }
}

