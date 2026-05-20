<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Order;
use App\Models\PrintJob;
use App\Models\Receipt;
use App\Support\HardwareValidation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrintJobController extends Controller
{
    use EnforcesTenantAccess;

    public function index(Request $request)
    {
        $jobs = $this->branchScoped(
            $request,
            PrintJob::query()->with(['device', 'order', 'receipt'])
        )
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->latest('id')
            ->paginate((int) $request->integer('per_page', 25));

        return response()->json($jobs);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id' => 'nullable|integer|exists:branches,id',
            'device_id' => 'nullable|integer|exists:devices,id',
            'device_uuid' => 'nullable|string|max:255',
            'order_id' => 'nullable|integer|exists:orders,id',
            'receipt_id' => 'nullable|integer|exists:receipts,id',
            'type' => 'nullable|in:receipt,kitchen_ticket,order_summary,cash_drawer',
            'priority' => 'nullable|integer|min:1|max:10',
            'payload' => 'nullable|array',
            'printer_profile' => 'nullable|string|max:100',
            'printer_endpoint' => 'nullable|string|max:255',
        ]);

        HardwareValidation::validatePrinterProfile($data['printer_profile'] ?? null);
        HardwareValidation::validatePrinterEndpoint($data['printer_endpoint'] ?? null);

        [$branchId, $restaurantId, $order, $receipt, $device] = $this->resolvePrintContext($request, $data);

        $job = PrintJob::query()->create([
            'restaurant_id' => $restaurantId,
            'branch_id' => $branchId,
            'device_id' => $device?->id,
            'order_id' => $order?->id,
            'receipt_id' => $receipt?->id,
            'type' => $data['type'] ?? 'receipt',
            'status' => 'queued',
            'priority' => $data['priority'] ?? 5,
            'payload' => $data['payload'] ?? $this->defaultPayload($order, $receipt),
            'printer_profile' => $data['printer_profile'] ?? $device?->printer_profile,
            'printer_endpoint' => $data['printer_endpoint'] ?? $device?->printer_endpoint,
            'queued_at' => now(),
        ]);

        return response()->json(['data' => $job->fresh(['device', 'order', 'receipt'])], 201);
    }

    public function show(Request $request, PrintJob $printJob)
    {
        $job = $this->branchScoped($request, PrintJob::query()->with(['device', 'order', 'receipt']))
            ->findOrFail($printJob->id);

        return response()->json(['data' => $job]);
    }

    public function claim(Request $request)
    {
        $data = $request->validate([
            'device_uuid' => 'required|string|max:255',
            'branch_id' => 'nullable|integer|exists:branches,id',
        ]);

        $device = Device::query()
            ->where('uuid', $data['device_uuid'])
            ->firstOrFail();
        $this->ensureBranchAccess($request, (int) $device->branch_id);

        $job = DB::transaction(function () use ($device) {
            $job = PrintJob::query()
                ->where('branch_id', $device->branch_id)
                ->where('status', 'queued')
                ->where(fn ($query) => $query
                    ->whereNull('device_id')
                    ->orWhere('device_id', $device->id))
                ->orderByDesc('priority')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (!$job) {
                return null;
            }

            $job->forceFill([
                'device_id' => $device->id,
                'status' => 'printing',
                'claimed_at' => now(),
                'attempts' => $job->attempts + 1,
            ])->save();

            return $job;
        });

        if (!$job) {
            return response()->json([
                'data' => null,
                'server_time' => now()->toISOString(),
            ]);
        }

        return response()->json([
            'data' => $job->fresh(['device', 'order', 'receipt']),
            'server_time' => now()->toISOString(),
        ]);
    }

    public function update(Request $request, PrintJob $printJob)
    {
        $job = $this->branchScoped($request, PrintJob::query())
            ->findOrFail($printJob->id);

        $data = $request->validate([
            'status' => 'required|in:queued,printing,printed,failed,cancelled',
            'error_message' => 'nullable|string|max:2000',
        ]);

        $job->forceFill([
            'status' => $data['status'],
            'error_message' => $data['error_message'] ?? null,
            'printed_at' => $data['status'] === 'printed' ? now() : $job->printed_at,
        ])->save();

        return response()->json(['data' => $job->fresh(['device', 'order', 'receipt'])]);
    }

    private function resolvePrintContext(Request $request, array $data): array
    {
        $order = isset($data['order_id'])
            ? Order::query()->with('branch.restaurant')->findOrFail($data['order_id'])
            : null;
        $receipt = isset($data['receipt_id'])
            ? Receipt::query()->with('order.branch.restaurant')->findOrFail($data['receipt_id'])
            : null;
        $device = $this->resolveDevice($data);
        $branchId = $order?->branch_id
            ?? $receipt?->order?->branch_id
            ?? $device?->branch_id
            ?? ($data['branch_id'] ?? null);

        $branchId = $this->defaultBranchIdForWrite($request, $branchId ? (int) $branchId : null);
        $this->ensureBranchAccess($request, $branchId);

        $restaurantId = $order?->branch?->restaurant_id
            ?? $receipt?->order?->branch?->restaurant_id
            ?? $device?->branch?->restaurant_id
            ?? \App\Models\Branch::query()->whereKey($branchId)->value('restaurant_id');

        abort_unless($restaurantId, 422, 'Print job must resolve to a restaurant.');

        return [$branchId, (int) $restaurantId, $order, $receipt, $device];
    }

    private function resolveDevice(array $data): ?Device
    {
        if (!empty($data['device_id'])) {
            return Device::query()->with('branch')->find($data['device_id']);
        }

        if (!empty($data['device_uuid'])) {
            return Device::query()->with('branch')->where('uuid', $data['device_uuid'])->first();
        }

        return null;
    }

    private function defaultPayload(?Order $order, ?Receipt $receipt): array
    {
        if ($receipt) {
            return [
                'receipt_id' => $receipt->id,
                'receipt_number' => $receipt->receipt_number,
                'content' => json_decode((string) $receipt->content, true),
            ];
        }

        if ($order) {
            $order->loadMissing(['table', 'items.product', 'payments']);

            return [
                'order_id' => $order->id,
                'table' => $order->table?->name,
                'total' => (float) $order->total,
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'name' => $item->product?->name,
                    'quantity' => $item->quantity,
                    'total' => (float) $item->total,
                ])->values()->all(),
            ];
        }

        return ['created_at' => now()->toISOString()];
    }
}
