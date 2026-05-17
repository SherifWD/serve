<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BranchOperationSettingController extends Controller
{
    use EnforcesTenantAccess;

    public function index(Request $request)
    {
        $query = Branch::query()
            ->with([
                'restaurant:id,name,kind,logo_url',
                'cashRegister',
                'devices' => fn ($devices) => $devices
                    ->whereIn('type', ['Receipt Printer', 'Printer', 'Cash Drawer'])
                    ->orderBy('name'),
            ])
            ->orderBy('name');

        $branchIds = $this->accessibleBranchIds($request);
        if ($branchIds !== null) {
            $query->whereIn('id', $branchIds);
        }

        if ($request->user()?->isPlatformAdmin()) {
            if ($request->filled('restaurant_id')) {
                $query->where('restaurant_id', $request->integer('restaurant_id'));
            }

            if ($request->filled('branch_id')) {
                $query->whereKey($request->integer('branch_id'));
            }
        }

        return response()->json([
            'data' => $query->get()->map(fn (Branch $branch) => $this->resource($branch))->values(),
        ]);
    }

    public function update(Request $request, Branch $branch)
    {
        $this->ensureBranchAccess($request, (int) $branch->id);

        $receiptPrinter = $this->receiptPrinterFor($branch);
        $cashDrawerDevice = $this->cashDrawerDeviceFor($branch);

        $receiptPrinterUuidRule = Rule::unique('devices', 'uuid');
        if ($receiptPrinter) {
            $receiptPrinterUuidRule->ignore($receiptPrinter->id);
        }

        $cashDrawerUuidRule = Rule::unique('devices', 'uuid');
        if ($cashDrawerDevice) {
            $cashDrawerUuidRule->ignore($cashDrawerDevice->id);
        }

        $data = $request->validate([
            'cash_drawer' => 'required|array',
            'cash_drawer.opening_balance' => 'nullable|numeric|min:0|max:99999999.99',
            'cash_drawer.closing_balance' => 'nullable|numeric|min:0|max:99999999.99',
            'cash_drawer.is_open' => 'nullable|boolean',
            'cash_drawer.name' => 'nullable|string|max:255',
            'cash_drawer.uuid' => ['nullable', 'string', 'max:255', $cashDrawerUuidRule],
            'cash_drawer.printer_endpoint' => 'nullable|string|max:255',
            'cash_drawer.is_active' => 'nullable|boolean',
            'receipt_printer' => 'required|array',
            'receipt_printer.name' => 'nullable|string|max:255',
            'receipt_printer.uuid' => ['nullable', 'string', 'max:255', $receiptPrinterUuidRule],
            'receipt_printer.printer_profile' => 'nullable|string|max:100',
            'receipt_printer.printer_paper_width_mm' => 'nullable|integer|min:40|max:120',
            'receipt_printer.printer_endpoint' => 'nullable|string|max:255',
            'receipt_printer.is_active' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($branch, $data, $receiptPrinter, $cashDrawerDevice): void {
            $drawer = $data['cash_drawer'];
            CashRegister::query()->updateOrCreate(
                ['branch_id' => $branch->id],
                [
                    'opening_balance' => $drawer['opening_balance'] ?? 0,
                    'closing_balance' => $drawer['closing_balance'] ?? null,
                    'is_open' => $drawer['is_open'] ?? true,
                ],
            );

            $cashDrawerDevice ??= new Device(['branch_id' => $branch->id]);
            $cashDrawerDevice->forceFill([
                'branch_id' => $branch->id,
                'name' => ($drawer['name'] ?? null) ?: "{$branch->name} Cash Drawer",
                'type' => 'Cash Drawer',
                'uuid' => ($drawer['uuid'] ?? null) ?: "branch-{$branch->id}-cash-drawer",
                'printer_profile' => null,
                'printer_paper_width_mm' => null,
                'printer_endpoint' => $drawer['printer_endpoint'] ?? null,
                'capabilities' => ['cash_drawer' => true],
                'is_active' => $drawer['is_active'] ?? true,
            ])->save();

            $printer = $data['receipt_printer'];
            $receiptPrinter ??= new Device(['branch_id' => $branch->id]);
            $receiptPrinter->forceFill([
                'branch_id' => $branch->id,
                'name' => ($printer['name'] ?? null) ?: "{$branch->name} Receipt Printer",
                'type' => 'Receipt Printer',
                'uuid' => ($printer['uuid'] ?? null) ?: "branch-{$branch->id}-receipt-printer",
                'printer_profile' => ($printer['printer_profile'] ?? null) ?: 'epson-thermal',
                'printer_paper_width_mm' => $printer['printer_paper_width_mm'] ?? 80,
                'printer_endpoint' => $printer['printer_endpoint'] ?? null,
                'capabilities' => ['receipt_printer' => true],
                'is_active' => $printer['is_active'] ?? true,
            ])->save();
        });

        return response()->json([
            'data' => $this->resource($branch->fresh([
                'restaurant:id,name,kind,logo_url',
                'cashRegister',
                'devices',
            ])),
        ]);
    }

    private function resource(Branch $branch): array
    {
        $cashRegister = $branch->cashRegister;
        $receiptPrinter = $this->receiptPrinterFor($branch);
        $cashDrawerDevice = $this->cashDrawerDeviceFor($branch);

        return [
            'id' => $branch->id,
            'name' => $branch->name,
            'location' => $branch->location,
            'restaurant' => $branch->restaurant,
            'cash_drawer' => [
                'register_id' => $cashRegister?->id,
                'opening_balance' => (float) ($cashRegister?->opening_balance ?? 0),
                'closing_balance' => $cashRegister?->closing_balance !== null
                    ? (float) $cashRegister->closing_balance
                    : null,
                'is_open' => (bool) ($cashRegister?->is_open ?? true),
                'device_id' => $cashDrawerDevice?->id,
                'name' => $cashDrawerDevice?->name ?? "{$branch->name} Cash Drawer",
                'uuid' => $cashDrawerDevice?->uuid ?? "branch-{$branch->id}-cash-drawer",
                'printer_endpoint' => $cashDrawerDevice?->printer_endpoint,
                'is_active' => (bool) ($cashDrawerDevice?->is_active ?? true),
            ],
            'receipt_printer' => [
                'device_id' => $receiptPrinter?->id,
                'name' => $receiptPrinter?->name ?? "{$branch->name} Receipt Printer",
                'uuid' => $receiptPrinter?->uuid ?? "branch-{$branch->id}-receipt-printer",
                'printer_profile' => $receiptPrinter?->printer_profile ?? 'epson-thermal',
                'printer_paper_width_mm' => $receiptPrinter?->printer_paper_width_mm ?? 80,
                'printer_endpoint' => $receiptPrinter?->printer_endpoint,
                'is_active' => (bool) ($receiptPrinter?->is_active ?? true),
            ],
        ];
    }

    private function receiptPrinterFor(Branch $branch): ?Device
    {
        if ($branch->relationLoaded('devices')) {
            return $branch->devices
                ->first(fn (Device $device) => in_array($device->type, ['Receipt Printer', 'Printer'], true));
        }

        return Device::query()
            ->where('branch_id', $branch->id)
            ->whereIn('type', ['Receipt Printer', 'Printer'])
            ->orderByRaw("case when type = 'Receipt Printer' then 0 else 1 end")
            ->first();
    }

    private function cashDrawerDeviceFor(Branch $branch): ?Device
    {
        if ($branch->relationLoaded('devices')) {
            return $branch->devices->firstWhere('type', 'Cash Drawer');
        }

        return Device::query()
            ->where('branch_id', $branch->id)
            ->where('type', 'Cash Drawer')
            ->first();
    }
}
