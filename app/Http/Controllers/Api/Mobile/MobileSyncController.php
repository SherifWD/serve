<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Device;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\PaymentProviderConfig;
use App\Models\PrintJob;
use App\Models\Product;
use App\Models\Table;
use App\Services\Inventory\ProductStockService;
use Illuminate\Http\Request;

class MobileSyncController extends Controller
{
    use EnforcesTenantAccess;

    public function state(Request $request)
    {
        $data = $request->validate([
            'branch_id' => 'nullable|integer|exists:branches,id',
            'since' => 'nullable|date',
            'surface' => 'nullable|in:waiter,cashier,kds,kitchen,customer,owner',
        ]);

        $branchId = $this->resolveBranchId($request, $data['branch_id'] ?? null);
        $since = isset($data['since']) ? now()->parse($data['since']) : null;
        $orderStatuses = match ($data['surface'] ?? null) {
            'cashier' => ['cashier', 'paid'],
            'kds', 'kitchen' => ['pending', 'open', 'running', 'preparing', 'cashier'],
            default => ['pending', 'open', 'running', 'cashier', 'paid'],
        };

        $orders = Order::query()
            ->where('branch_id', $branchId)
            ->whereIn('status', $orderStatuses)
            ->when($since, fn ($query) => $query->where('updated_at', '>', $since))
            ->with(['table', 'customer', 'items.product', 'items.modifiers.modifier', 'payments'])
            ->latest('updated_at')
            ->limit(100)
            ->get();
        $stock = app(ProductStockService::class);
        $products = Product::query()
            ->where('branch_id', $branchId)
            ->where('is_available', true)
            ->when($since, fn ($query) => $query->where('updated_at', '>', $since))
            ->with(['category', 'recipe.ingredients'])
            ->orderBy('name')
            ->limit(250)
            ->get()
            ->filter(fn (Product $product) => $stock->isAvailable($product, (int) $branchId))
            ->values();
        $products->each(fn (Product $product) => $product->unsetRelation('recipe'));

        return response()->json([
            'server_time' => now()->toISOString(),
            'branch_id' => $branchId,
            'surface' => $data['surface'] ?? null,
            'polling' => [
                'mode' => config('broadcasting.default') === 'reverb' ? 'broadcast-preferred' : 'polling',
                'tables_seconds' => 5,
                'orders_seconds' => 5,
                'kds_seconds' => 3,
                'full_refresh_seconds' => 60,
                'retry_backoff_seconds' => [2, 5, 10, 20],
            ],
            'offline' => [
                'client_mutation_ids' => true,
                'queueable_actions' => [
                    'create_order',
                    'send_to_kds',
                    'send_to_cashier',
                    'payment_attempt',
                    'pay_order',
                    'print_receipt',
                    'claim_print_job',
                ],
                'conflict_policy' => 'server_updated_at_wins',
                'mutation_id_header' => 'X-Client-Mutation-Id',
            ],
            'hardware' => [
                'payment_attempts' => true,
                'server_print_queue' => true,
                'printer_claim_endpoint' => '/api/mobile/print-jobs/claim',
            ],
            'data' => [
                'tables' => Table::query()
                    ->where('branch_id', $branchId)
                    ->when($since, fn ($query) => $query->where('updated_at', '>', $since))
                    ->orderBy('name')
                    ->get(),
                'orders' => $orders,
                'products' => $products,
                'inventory_alerts' => InventoryItem::query()
                    ->where('branch_id', $branchId)
                    ->whereColumn('quantity', '<=', 'min_stock')
                    ->orderBy('name')
                    ->limit(50)
                    ->get(['id', 'name', 'unit', 'quantity', 'min_stock', 'updated_at']),
                'devices' => Device::query()
                    ->where('branch_id', $branchId)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(),
                'payment_providers' => PaymentProviderConfig::query()
                    ->where('restaurant_id', Branch::query()->whereKey($branchId)->value('restaurant_id'))
                    ->where(fn ($query) => $query->whereNull('branch_id')->orWhere('branch_id', $branchId))
                    ->where('is_active', true)
                    ->orderBy('display_name')
                    ->get(),
                'print_jobs' => PrintJob::query()
                    ->where('branch_id', $branchId)
                    ->whereIn('status', ['queued', 'printing', 'failed'])
                    ->latest('id')
                    ->limit(25)
                    ->get(),
            ],
        ]);
    }

    public function heartbeat(Request $request)
    {
        $data = $request->validate([
            'uuid' => 'required|string|max:255',
            'name' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:50',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'payment_provider' => 'nullable|string|max:100',
            'printer_profile' => 'nullable|string|max:100',
            'printer_paper_width_mm' => 'nullable|integer|min:40|max:120',
            'printer_endpoint' => 'nullable|string|max:255',
            'capabilities' => 'nullable|array',
        ]);

        $branchId = $this->resolveBranchId($request, $data['branch_id'] ?? null);
        $device = Device::query()->updateOrCreate(
            ['uuid' => $data['uuid']],
            [
                'name' => $data['name'] ?? $request->user()?->name.' device',
                'type' => $data['type'] ?? 'POS',
                'branch_id' => $branchId,
                'payment_provider' => $data['payment_provider'] ?? null,
                'printer_profile' => $data['printer_profile'] ?? null,
                'printer_paper_width_mm' => $data['printer_paper_width_mm'] ?? null,
                'printer_endpoint' => $data['printer_endpoint'] ?? null,
                'capabilities' => $data['capabilities'] ?? null,
                'is_active' => true,
                'last_seen_at' => now(),
            ],
        );

        return response()->json([
            'data' => $device->fresh('branch'),
            'server_time' => now()->toISOString(),
        ]);
    }

    private function resolveBranchId(Request $request, ?int $branchId): int
    {
        if ($branchId) {
            $this->ensureBranchAccess($request, $branchId);
            return $branchId;
        }

        if ($request->user()?->branch_id) {
            return (int) $request->user()->branch_id;
        }

        if ($request->user()?->restaurant_id) {
            $resolved = Branch::query()
                ->where('restaurant_id', $request->user()->restaurant_id)
                ->orderBy('id')
                ->value('id');

            if ($resolved) {
                return (int) $resolved;
            }
        }

        abort(422, 'A branch is required for mobile sync.');
    }
}
