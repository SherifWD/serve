<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryTransactionController extends Controller
{
    use EnforcesTenantAccess;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $transactions = $this->branchRelationScoped($request, InventoryTransaction::query(), 'inventoryItem.branch')
            ->with(['inventoryItem.branch'])
            ->latest()
            ->paginate(50);

        return response()->json($transactions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'inventory_item_id' => 'required|integer|exists:inventory_items,id',
            'type' => 'required|in:in,out,adjustment',
            'quantity' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:255',
        ]);

        $transaction = DB::transaction(function () use ($data, $request) {
            $item = $this->branchScoped($request, InventoryItem::query())
                ->lockForUpdate()
                ->findOrFail($data['inventory_item_id']);
            $quantity = (float) $data['quantity'];
            $nextQuantity = match ($data['type']) {
                'in' => (float) $item->quantity + $quantity,
                'out' => max(0, (float) $item->quantity - $quantity),
                'adjustment' => $quantity,
            };

            $item->quantity = $nextQuantity;
            $item->save();

            return InventoryTransaction::query()->create([
                'inventory_item_id' => $item->id,
                'type' => $data['type'],
                'quantity' => $quantity,
                'reason' => $data['reason'] ?? null,
            ]);
        });

        return response()->json(['data' => $transaction->load('inventoryItem.branch')], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $transaction = $this->branchRelationScoped($request, InventoryTransaction::query(), 'inventoryItem.branch')
            ->with(['inventoryItem.branch'])
            ->findOrFail($id);

        return response()->json(['data' => $transaction]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $transaction = $this->branchRelationScoped($request, InventoryTransaction::query(), 'inventoryItem.branch')
            ->findOrFail($id);

        $data = $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $transaction->update($data);

        return response()->json(['data' => $transaction->fresh('inventoryItem.branch')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $transaction = $this->branchRelationScoped($request, InventoryTransaction::query(), 'inventoryItem.branch')
            ->findOrFail($id);
        $transaction->delete();

        return response()->json(['message' => 'Inventory transaction deleted']);
    }
}
