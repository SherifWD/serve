<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\InventoryItem;
use App\Models\Product;
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
            'name'           => 'nullable|string|max:255',
            'branch_id'      => 'required|exists:branches,id',
            'unit'           => 'required|string|max:255',
            'quantity'       => 'required|numeric',
            'min_stock'      => 'required|numeric',
            'ingredient_id'  => 'nullable|exists:ingredients,id',
            'product_id'     => 'nullable|exists:products,id',
        ]);

        $ingredientId = $data['ingredient_id'] ?? null;
        $productId = $data['product_id'] ?? null;

        if (!$ingredientId && !$productId) {
            return response()->json(['error' => 'ingredient_id or product_id is required'], 422);
        }
        if ($ingredientId && $productId) {
            return response()->json(['error' => 'Only one of ingredient_id or product_id should be set'], 422);
        }

        $data['name'] = $this->resolveName($data);
        $inventoryItem = InventoryItem::create($data);
        return response()->json(['data' => $inventoryItem->load(['ingredient', 'product'])], 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name'           => 'nullable|string|max:255',
            'branch_id'      => 'required|exists:branches,id',
            'unit'           => 'required|string|max:255',
            'quantity'       => 'required|numeric',
            'min_stock'      => 'required|numeric',
            'ingredient_id'  => 'nullable|exists:ingredients,id',
            'product_id'     => 'nullable|exists:products,id',
        ]);

        $ingredientId = $data['ingredient_id'] ?? null;
        $productId = $data['product_id'] ?? null;

        if (!$ingredientId && !$productId) {
            return response()->json(['error' => 'ingredient_id or product_id is required'], 422);
        }
        if ($ingredientId && $productId) {
            return response()->json(['error' => 'Only one of ingredient_id or product_id should be set'], 422);
        }

        $inventoryItem = InventoryItem::findOrFail($id);
        $data['name'] = $this->resolveName($data);
        $inventoryItem->update($data);
        return response()->json(['data' => $inventoryItem->load(['ingredient', 'product'])]);
    }

    public function destroy($id)
    {
        $inventoryItem = InventoryItem::findOrFail($id);
        $inventoryItem->delete();
        return response()->json(['message' => 'Item deleted successfully']);
    }

    private function resolveName(array $data): string
    {
        if (!empty($data['name'])) {
            return $data['name'];
        }

        if (!empty($data['ingredient_id'])) {
            return Ingredient::query()->findOrFail($data['ingredient_id'])->name;
        }

        if (!empty($data['product_id'])) {
            return Product::query()->findOrFail($data['product_id'])->name;
        }

        return 'Inventory Item';
    }
}
