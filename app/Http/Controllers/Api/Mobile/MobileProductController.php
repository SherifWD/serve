<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class MobileProductController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $branchId = $user?->branch_id;

        $categories = Category::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->with([
                'products' => function ($query) use ($branchId) {
                    $query->select('id', 'name', 'price', 'category_id', 'image', 'branch_id')
                        ->when($branchId, fn ($inner) => $inner->where('branch_id', $branchId))
                        ->where('is_available', true)
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

        $categories = $categories
            ->filter(fn ($category) => $category->products->isNotEmpty())
            ->values();

        return response()->json(['data' => $categories]);
    }
}
