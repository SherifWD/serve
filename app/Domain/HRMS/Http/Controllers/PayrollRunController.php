<?php

namespace App\Domain\HRMS\Http\Controllers;

use App\Domain\HRMS\Http\Requests\PayrollRunStoreRequest;
use App\Domain\HRMS\Http\Requests\PayrollRunUpdateRequest;
use App\Domain\HRMS\Http\Resources\PayrollRunResource;
use App\Domain\HRMS\Models\PayrollEntry;
use App\Domain\HRMS\Models\PayrollRun;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollRunController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $runs = PayrollRun::query()
            ->with('entries.worker')
            ->where('tenant_id', $tenantId)
            ->when($request->query('period'), fn ($q, $period) => $q->where('period', $period))
            ->orderByDesc('pay_date')
            ->paginate($request->integer('per_page', 20));

        return PayrollRunResource::collection($runs)->response();
    }

    public function store(PayrollRunStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $data = $request->validated();

        $payrollRun = DB::transaction(function () use ($tenantId, $data) {
            $run = PayrollRun::create([
                'tenant_id' => $tenantId,
                'reference' => $data['reference'],
                'period' => $data['period'],
                'status' => $data['status'] ?? 'draft',
                'pay_date' => $data['pay_date'] ?? null,
            ]);

            $grossTotal = 0;
            $netTotal = 0;

            foreach ($data['entries'] ?? [] as $entry) {
                $grossTotal += $entry['gross_amount'];
                $netTotal += $entry['net_amount'];

                PayrollEntry::create([
                    'tenant_id' => $tenantId,
                    'payroll_run_id' => $run->id,
                    'worker_id' => $entry['worker_id'],
                    'gross_amount' => $entry['gross_amount'],
                    'net_amount' => $entry['net_amount'],
                    'breakdown' => $entry['breakdown'] ?? null,
                ]);
            }

            $run->update([
                'gross_total' => round($grossTotal, 2),
                'net_total' => round($netTotal, 2),
            ]);

            return $run->load('entries.worker');
        });

        return PayrollRunResource::make($payrollRun)->response()->setStatusCode(201);
    }

    public function show(PayrollRun $payrollRun): JsonResponse
    {
        $this->authorizeTenantResource($payrollRun);

        return PayrollRunResource::make($payrollRun->load('entries.worker'))->response();
    }

    public function update(PayrollRunUpdateRequest $request, PayrollRun $payrollRun): JsonResponse
    {
        $this->authorizeTenantResource($payrollRun);
        $data = $request->validated();
        $tenantId = $payrollRun->tenant_id;

        $payrollRun = DB::transaction(function () use ($payrollRun, $data, $tenantId) {
            $payrollRun->update($data);

            if (isset($data['entries'])) {
                foreach ($data['entries'] as $entry) {
                    if (($entry['_action'] ?? null) === 'delete' && !empty($entry['id'])) {
                        PayrollEntry::query()
                            ->where('tenant_id', $tenantId)
                            ->where('id', $entry['id'])
                            ->delete();
                        continue;
                    }

                    PayrollEntry::updateOrCreate(
                        [
                            'id' => $entry['id'] ?? null,
                            'tenant_id' => $tenantId,
                        ],
                        [
                            'tenant_id' => $tenantId,
                            'payroll_run_id' => $payrollRun->id,
                            'worker_id' => $entry['worker_id'],
                            'gross_amount' => $entry['gross_amount'],
                            'net_amount' => $entry['net_amount'],
                            'breakdown' => $entry['breakdown'] ?? null,
                        ]
                    );
                }
            }

            $totals = PayrollEntry::query()
                ->where('tenant_id', $tenantId)
                ->where('payroll_run_id', $payrollRun->id)
                ->selectRaw('SUM(gross_amount) as gross_total, SUM(net_amount) as net_total')
                ->first();

            $payrollRun->update([
                'gross_total' => round((float) ($totals->gross_total ?? 0), 2),
                'net_total' => round((float) ($totals->net_total ?? 0), 2),
            ]);

            return $payrollRun->load('entries.worker');
        });

        return PayrollRunResource::make($payrollRun)->response();
    }

    public function destroy(PayrollRun $payrollRun): JsonResponse
    {
        $this->authorizeTenantResource($payrollRun);
        $payrollRun->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(PayrollRun $payrollRun): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($payrollRun->tenant_id !== $tenantId, 404);
    }
}

