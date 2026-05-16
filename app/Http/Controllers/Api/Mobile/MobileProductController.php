<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Inventory\ProductStockService;
use Illuminate\Http\Request;

class MobileProductController extends Controller
{
    public function index(Request $request, ProductStockService $stock)
    {
        $user = $request->user();
        $branchId = $user?->branch_id;

        $categories = Category::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->with([
                'products' => function ($query) use ($branchId) {
                    $query->select('id', 'name', 'price', 'category_id', 'image', 'branch_id', 'stock')
                        ->when($branchId, fn ($inner) => $inner->where('branch_id', $branchId))
                        ->where('is_available', true)
                        ->with('recipe.ingredients')
                        ->orderBy('name');
                },
                'questions' => function ($query) {
                    $query->select('id', 'category_id', 'question', 'image');
                },
                'questions.choices' => function ($query) {
                    $query->select('id', 'question_id', 'choice', 'image');
                },
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'branch_id']);

        $categories->each(function ($category) use ($stock) {
            $products = $category->products
                ->filter(fn ($product) => $product->branch_id && $stock->isAvailable($product, (int) $product->branch_id))
                ->values();

            $products->each(fn ($product) => $product->unsetRelation('recipe'));

            $category->setRelation('products', $products);
        });

        $categories = $categories
            ->filter(fn ($category) => $category->products->isNotEmpty())
            ->values();

        return response()->json(['data' => $categories]);
    }
}
