<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Ingredient;
use App\Models\InventoryAdjustment;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Services\Inventory\InventoryMovementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventoryOperationController extends Controller
{
    use EnforcesTenantAccess;

    public function receivePurchase(Request $request, InventoryMovementService $movement)
    {
        $data = $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'reference_code' => 'nullable|string|max:100|unique:purchase_orders,reference_code',
            'order_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'nullable|integer|exists:inventory_items,id',
            'items.*.name' => 'required_without:items.*.inventory_item_id|string|max:255',
            'items.*.unit' => 'required_without:items.*.inventory_item_id|string|max:50',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
            'items.*.min_stock' => 'nullable|numeric|min:0',
        ]);

        $branchId = $this->branchIdForWrite($request, (int) $data['branch_id']);

        if (!empty($data['supplier_id'])) {
            $supplier = Supplier::query()->findOrFail($data['supplier_id']);
            $this->ensureRestaurantAccess($request, $supplier->restaurant_id);
        }

        $purchase = DB::transaction(function () use ($data, $branchId, $movement) {
            $reference = $data['reference_code'] ?? $this->reference('PO');
            $normalizedItems = collect($data['items'])->map(function (array $line) use ($branchId): array {
                $item = $this->inventoryItemForLine($line, $branchId);

                return [
                    'inventory_item_id' => $item->id,
                    'name' => $item->name,
                    'unit' => $item->unit,
                    'quantity' => round((float) $line['quantity'], 3),
                    'unit_cost' => round((float) ($line['unit_cost'] ?? 0), 2),
                ];
            })->values();

            $purchase = PurchaseOrder::query()->create([
                'supplier_id' => $data['supplier_id'] ?? null,
                'branch_id' => $branchId,
                'reference_code' => $reference,
                'total_cost' => round($normalizedItems->sum(fn ($line) => $line['quantity'] * $line['unit_cost']), 2),
                'status' => 'received',
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'items' => $normalizedItems->all(),
                'received_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($normalizedItems as $line) {
                $item = InventoryItem::query()->lockForUpdate()->findOrFail($line['inventory_item_id']);
                $movement->add($item, (float) $line['quantity'], 'Purchase receiving', PurchaseOrder::class, $purchase->id, $reference);
            }

            return $purchase;
        });

        return response()->json(['data' => $purchase->load(['branch', 'supplier'])], 201);
    }

    public function transfer(Request $request, InventoryMovementService $movement)
    {
        $data = $request->validate([
            'from_branch_id' => 'required|integer|exists:branches,id',
            'to_branch_id' => 'required|integer|different:from_branch_id|exists:branches,id',
            'reference_code' => 'nullable|string|max:100|unique:stock_transfers,reference_code',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|integer|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
        ]);

        $fromBranchId = $this->branchIdForWrite($request, (int) $data['from_branch_id']);
        $this->ensureBranchAccess($request, (int) $data['to_branch_id']);
        $this->ensureSameRestaurantBranches($fromBranchId, (int) $data['to_branch_id']);

        $transfer = DB::transaction(function () use ($data, $fromBranchId, $movement) {
            $reference = $data['reference_code'] ?? $this->reference('TR');
            $toBranchId = (int) $data['to_branch_id'];
            $normalizedItems = collect($data['items'])->map(function (array $line) use ($fromBranchId, $toBranchId): array {
                $source = InventoryItem::query()
                    ->where('branch_id', $fromBranchId)
                    ->lockForUpdate()
                    ->findOrFail($line['inventory_item_id']);
                $quantity = round((float) $line['quantity'], 3);

                if ((float) $source->quantity < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => "{$source->name} does not have enough stock to transfer.",
                    ]);
                }

                $target = InventoryItem::query()->firstOrCreate(
                    [
                        'branch_id' => $toBranchId,
                        'name' => $source->name,
                        'unit' => $source->unit,
                        'ingredient_id' => $source->ingredient_id,
                        'product_id' => null,
                    ],
                    [
                        'quantity' => 0,
                        'min_stock' => $source->min_stock ?? 0,
                    ],
                );

                return [
                    'inventory_item_id' => $source->id,
                    'target_inventory_item_id' => $target->id,
                    'name' => $source->name,
                    'unit' => $source->unit,
                    'quantity' => $quantity,
                ];
            })->values();

            $transfer = StockTransfer::query()->create([
                'from_branch_id' => $fromBranchId,
                'to_branch_id' => $toBranchId,
                'reference_code' => $reference,
                'total_quantity' => $normalizedItems->sum('quantity'),
                'items' => $normalizedItems->all(),
                'status' => 'completed',
                'completed_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($normalizedItems as $line) {
                $source = InventoryItem::query()->lockForUpdate()->findOrFail($line['inventory_item_id']);
                $target = InventoryItem::query()->lockForUpdate()->findOrFail($line['target_inventory_item_id']);
                $movement->remove($source, (float) $line['quantity'], 'Stock transfer out', StockTransfer::class, $transfer->id, $reference);
                $movement->add($target, (float) $line['quantity'], 'Stock transfer in', StockTransfer::class, $transfer->id, $reference);
            }

            return $transfer;
        });

        return response()->json(['data' => $transfer->load(['fromBranch', 'toBranch'])], 201);
    }

    public function stockCount(Request $request, InventoryMovementService $movement)
    {
        $data = $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'reference_code' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|integer|exists:inventory_items,id',
            'items.*.counted_quantity' => 'required|numeric|min:0',
            'items.*.reason' => 'nullable|string|max:500',
        ]);

        $branchId = $this->branchIdForWrite($request, (int) $data['branch_id']);
        $reference = $data['reference_code'] ?? $this->reference('CNT');

        $adjustments = DB::transaction(function () use ($data, $request, $branchId, $reference, $movement) {
            return collect($data['items'])->map(function (array $line) use ($request, $branchId, $reference, $movement) {
                $item = $this->branchScoped($request, InventoryItem::query())
                    ->where('branch_id', $branchId)
                    ->lockForUpdate()
                    ->findOrFail($line['inventory_item_id']);
                $before = round((float) $item->quantity, 3);
                $after = round((float) $line['counted_quantity'], 3);

                $adjustment = InventoryAdjustment::query()->create([
                    'branch_id' => $branchId,
                    'inventory_item_id' => $item->id,
                    'ingredient_id' => $this->ingredientIdForAdjustment($item),
                    'type' => $after >= $before ? 'restock' : 'use',
                    'operation' => 'stock_count',
                    'quantity' => abs($after - $before),
                    'before_quantity' => $before,
                    'after_quantity' => $after,
                    'reason' => $line['reason'] ?? 'Stock count',
                    'reference_code' => $reference,
                ]);

                $movement->set($item, $after, 'Stock count', InventoryAdjustment::class, $adjustment->id, $reference);

                return $adjustment->fresh('inventoryItem');
            })->values();
        });

        return response()->json(['data' => $adjustments], 201);
    }

    public function adjust(Request $request, InventoryMovementService $movement)
    {
        $data = $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'inventory_item_id' => 'required|integer|exists:inventory_items,id',
            'operation' => 'required|in:restock,use,return,waste,comp,adjustment',
            'quantity' => 'required_unless:operation,adjustment|numeric|min:0.001',
            'target_quantity' => 'required_if:operation,adjustment|numeric|min:0',
            'reason' => 'nullable|string|max:500',
            'reference_code' => 'nullable|string|max:100',
        ]);

        $adjustment = DB::transaction(function () use ($data, $request, $movement) {
            $branchId = $this->branchIdForWrite($request, (int) $data['branch_id']);
            $item = $this->branchScoped($request, InventoryItem::query())
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->findOrFail($data['inventory_item_id']);
            $before = round((float) $item->quantity, 3);
            $reference = $data['reference_code'] ?? $this->reference('ADJ');
            $operation = $data['operation'];

            if ($operation === 'adjustment') {
                $after = round((float) $data['target_quantity'], 3);
                $quantity = abs($after - $before);
            } else {
                $quantity = round((float) $data['quantity'], 3);
                $after = in_array($operation, ['restock', 'return'], true)
                    ? $before + $quantity
                    : max(0, $before - $quantity);
            }

            $adjustment = InventoryAdjustment::query()->create([
                'branch_id' => $branchId,
                'inventory_item_id' => $item->id,
                'ingredient_id' => $this->ingredientIdForAdjustment($item),
                'type' => $operation === 'adjustment' ? ($after >= $before ? 'restock' : 'use') : $operation,
                'operation' => $operation,
                'quantity' => $quantity,
                'before_quantity' => $before,
                'after_quantity' => $after,
                'reason' => $data['reason'] ?? ucfirst($operation),
                'reference_code' => $reference,
            ]);

            if ($operation === 'adjustment') {
                $movement->set($item, $after, 'Manual adjustment', InventoryAdjustment::class, $adjustment->id, $reference);
            } elseif (in_array($operation, ['restock', 'return'], true)) {
                $movement->add($item, $quantity, ucfirst($operation), InventoryAdjustment::class, $adjustment->id, $reference);
            } else {
                $movement->remove($item, $quantity, ucfirst($operation), InventoryAdjustment::class, $adjustment->id, $reference);
            }

            return $adjustment->fresh('inventoryItem');
        });

        return response()->json(['data' => $adjustment], 201);
    }

    public function wastage(Request $request, InventoryMovementService $movement)
    {
        $request->merge(['operation' => 'waste']);

        return $this->adjust($request, $movement);
    }

    private function inventoryItemForLine(array $line, int $branchId): InventoryItem
    {
        if (!empty($line['inventory_item_id'])) {
            return InventoryItem::query()
                ->where('branch_id', $branchId)
                ->findOrFail($line['inventory_item_id']);
        }

        return InventoryItem::query()->firstOrCreate(
            [
                'branch_id' => $branchId,
                'name' => $line['name'],
                'unit' => $line['unit'],
            ],
            [
                'quantity' => 0,
                'min_stock' => $line['min_stock'] ?? 0,
            ],
        );
    }

    private function reference(string $prefix): string
    {
        return $prefix.'-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
    }

    private function ingredientIdForAdjustment(InventoryItem $item): int
    {
        if ($item->ingredient_id) {
            return (int) $item->ingredient_id;
        }

        $ingredient = Ingredient::query()->firstOrCreate(
            [
                'name' => $item->name,
                'unit' => $item->unit,
            ],
            [
                'stock' => 0,
                'min_stock' => 0,
            ],
        );

        $item->ingredient_id = $ingredient->id;
        $item->save();

        return (int) $ingredient->id;
    }

    private function ensureSameRestaurantBranches(int $fromBranchId, int $toBranchId): void
    {
        $branches = Branch::query()->whereIn('id', [$fromBranchId, $toBranchId])->pluck('restaurant_id', 'id');
        abort_unless(
            $branches->count() === 2 && (int) $branches[$fromBranchId] === (int) $branches[$toBranchId],
            422,
            'Stock transfers must stay inside one restaurant.'
        );
    }
}
