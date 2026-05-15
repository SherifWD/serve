<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use EnforcesTenantAccess;

    public function index(Request $request)
    {
        $query = $this->branchScoped($request, Category::query());
        return response()->json($query->with('branch')->latest()->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'required|integer|exists:branches,id',
        ]);
        $data['branch_id'] = $this->branchIdForWrite($request, (int) $data['branch_id']);
        $category = Category::create($data);
        return response()->json($category, 201);
    }

    public function show(Request $request, $id)
    {
        return $this->branchScoped($request, Category::with('branch'))->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $category = $this->branchScoped($request, Category::query())->findOrFail($id);
        $data = $request->validate([
            'name' => 'string|max:255',
            'branch_id' => 'integer|exists:branches,id',
        ]);
        if (array_key_exists('branch_id', $data)) {
            $data['branch_id'] = $this->branchIdForWrite($request, (int) $data['branch_id']);
        }
        $category->update($data);
        return response()->json($category);
    }

    public function destroy(Request $request, $id)
    {
        $category = $this->branchScoped($request, Category::query())->findOrFail($id);
        $category->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
