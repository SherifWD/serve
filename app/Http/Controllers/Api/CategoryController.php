<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::query();
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        return response()->json($query->with('branch')->latest()->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'required|integer|exists:branches,id',
        ]);
        $category = Category::create($data);
        return response()->json($category, 201);
    }

    public function show($id)
    {
        return Category::with('branch')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        $data = $request->validate([
            'name' => 'string|max:255',
            'branch_id' => 'integer|exists:branches,id',
        ]);
        $category->update($data);
        return response()->json($category);
    }

    public function destroy($id)
    {
        Category::destroy($id);
        return response()->json(['message' => 'Deleted']);
    }
}
