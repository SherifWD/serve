<?php

namespace App\Domain\Budgeting\Http\Controllers;

use App\Domain\Budgeting\Models\Actual;
use App\Domain\Budgeting\Models\Budget;
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

        $budgets = $this->filteredBudgets($tenantId, $request);
        $actuals = $this->matchingActuals($tenantId, $budgets, $request);

        $approvedFallbackSum = $budgets->sum(fn (Budget $budget) => $budget->approved_amount ?? $budget->planned_amount);

        $totals = [
            'planned' => $budgets->sum('planned_amount'),
            'approved' => $approvedFallbackSum,
            'forecast' => $budgets->sum('forecast_amount'),
            'actual' => $actuals->sum('actual_amount'),
        ];

        $byCostCenter = $budgets
            ->groupBy('cost_center_id')
            ->map(function (Collection $group) use ($actuals) {
                /** @var \App\Domain\Budgeting\Models\Budget $first */
                $first = $group->first();
                $actualTotal = $actuals
                    ->where('cost_center_id', $first->cost_center_id)
                    ->sum('actual_amount');

                $approvedTotal = $group->sum(fn (Budget $budget) => $budget->approved_amount ?? $budget->planned_amount);

                return [
                    'cost_center_id' => $first->cost_center_id,
                    'cost_center' => $first->costCenter?->name,
                    'planned' => $group->sum('planned_amount'),
                    'approved' => $approvedTotal,
                    'forecast' => $group->sum('forecast_amount'),
                    'actual' => $actualTotal,
                    'variance' => $approvedTotal - $actualTotal,
                ];
            })
            ->values();

        $byPeriod = $budgets
            ->groupBy('period')
            ->map(function (Collection $group, $period) use ($actuals) {
                $actualTotal = $actuals
                    ->whereIn('cost_center_id', $group->pluck('cost_center_id'))
                    ->where('period', $period)
                    ->sum('actual_amount');

                $approvedTotal = $group->sum(fn (Budget $budget) => $budget->approved_amount ?? $budget->planned_amount);

                return [
                    'period' => $period,
                    'planned' => $group->sum('planned_amount'),
                    'approved' => $approvedTotal,
                    'forecast' => $group->sum('forecast_amount'),
                    'actual' => $actualTotal,
                    'variance' => $approvedTotal - $actualTotal,
                ];
            })
            ->values();

        return response()->json([
            'filters' => $request->only(['cost_center_id', 'fiscal_year', 'status', 'period']),
            'totals' => $this->formatCurrencyArray($totals),
            'cost_centers' => $this->formatCurrencyCollection($byCostCenter),
            'periods' => $this->formatCurrencyCollection($byPeriod),
        ]);
    }

    public function export(Request $request)
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $budgets = $this->filteredBudgets($tenantId, $request);
        $actuals = $this->matchingActuals($tenantId, $budgets, $request)
            ->keyBy(fn ($actual) => "{$actual->cost_center_id}|{$actual->period}|{$actual->source_reference}");

        $headers = [
            'cost_center',
            'period',
            'fiscal_year',
            'status',
            'planned_amount',
            'approved_amount',
            'forecast_amount',
            'actual_amount',
            'variance',
        ];

        $rows = $budgets->map(function (Budget $budget) use ($actuals) {
            $actualForPeriod = $actuals
                ->where('cost_center_id', $budget->cost_center_id)
                ->where('period', $budget->period)
                ->sum('actual_amount');

            $approved = $budget->approved_amount ?? $budget->planned_amount;
            $variance = $approved - $actualForPeriod;

            return [
                'cost_center' => $budget->costCenter?->name,
                'period' => $budget->period,
                'fiscal_year' => $budget->fiscal_year,
                'status' => $budget->status,
                'planned_amount' => number_format((float) $budget->planned_amount, 2, '.', ''),
                'approved_amount' => number_format((float) ($budget->approved_amount ?? 0), 2, '.', ''),
                'forecast_amount' => number_format((float) ($budget->forecast_amount ?? 0), 2, '.', ''),
                'actual_amount' => number_format((float) $actualForPeriod, 2, '.', ''),
                'variance' => number_format((float) $variance, 2, '.', ''),
            ];
        });

        return CsvExporter::stream(
            sprintf('budgeting-report-%s', now()->format('Ymd_His')),
            $headers,
            $rows
        );
    }

    protected function filteredBudgets(int $tenantId, Request $request): Collection
    {
        return Budget::query()
            ->with('costCenter')
            ->where('tenant_id', $tenantId)
            ->when($request->filled('cost_center_id'), fn ($query) => $query->where('cost_center_id', $request->integer('cost_center_id')))
            ->when($request->filled('fiscal_year'), fn ($query) => $query->where('fiscal_year', $request->string('fiscal_year')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('period'), fn ($query) => $query->where('period', $request->string('period')))
            ->get();
    }

    protected function matchingActuals(int $tenantId, Collection $budgets, Request $request): Collection
    {
        $costCenterIds = $budgets->pluck('cost_center_id')->unique();

        if ($costCenterIds->isEmpty()) {
            return collect();
        }

        return Actual::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('cost_center_id', $costCenterIds)
            ->when($request->filled('period'), fn ($query) => $query->where('period', $request->string('period')))
            ->when($request->filled('fiscal_year'), fn ($query) => $query->where('fiscal_year', $request->string('fiscal_year')))
            ->get();
    }

    protected function formatCurrencyArray(array $values): array
    {
        return collect($values)
            ->map(fn ($value) => number_format((float) $value, 2, '.', ''))
            ->all();
    }

    protected function formatCurrencyCollection(Collection $collection): Collection
    {
        return $collection->map(function (array $row) {
            foreach (['planned', 'approved', 'forecast', 'actual', 'variance'] as $field) {
                if (array_key_exists($field, $row)) {
                    $row[$field] = number_format((float) $row[$field], 2, '.', '');
                }
            }
            return $row;
        });
    }
}
