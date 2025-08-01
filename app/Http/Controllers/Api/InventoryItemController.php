<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;

class InventoryItemController extends Controller
{
    public function index()
    {
        $inventoryItems = InventoryItem::with(['branch', 'ingredient', 'product'])->get();
        return response()->json(['data' => $inventoryItems]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id'      => 'required|exists:branches,id',
            'unit'           => 'required|string|max:255',
            'quantity'       => 'required|numeric',
            'min_stock'      => 'required|numeric',
            'ingredient_id'  => 'nullable|exists:ingredients,id',
            'product_id'     => 'nullable|exists:products,id',
        ]);

        if (!$data['ingredient_id'] && !$data['product_id']) {
            return response()->json(['error' => 'ingredient_id or product_id is required'], 422);
        }
        if ($data['ingredient_id'] && $data['product_id']) {
            return response()->json(['error' => 'Only one of ingredient_id or product_id should be set'], 422);
        }

        $inventoryItem = InventoryItem::create($data);
        return response()->json(['data' => $inventoryItem->load(['ingredient', 'product'])], 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'branch_id'      => 'required|exists:branches,id',
            'unit'           => 'required|string|max:255',
            'quantity'       => 'required|numeric',
            'min_stock'      => 'required|numeric',
            'ingredient_id'  => 'nullable|exists:ingredients,id',
            'product_id'     => 'nullable|exists:products,id',
        ]);

        if (!$data['ingredient_id'] && !$data['product_id']) {
            return response()->json(['error' => 'ingredient_id or product_id is required'], 422);
        }
        if ($data['ingredient_id'] && $data['product_id']) {
            return response()->json(['error' => 'Only one of ingredient_id or product_id should be set'], 422);
        }

        $inventoryItem = InventoryItem::findOrFail($id);
        $inventoryItem->update($data);
        return response()->json(['data' => $inventoryItem->load(['ingredient', 'product'])]);
    }

    public function destroy($id)
    {
        $inventoryItem = InventoryItem::findOrFail($id);
        $inventoryItem->delete();
        return response()->json(['message' => 'Item deleted successfully']);
    }
}
