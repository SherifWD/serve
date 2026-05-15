<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Models\Menu;
use App\Models\Category;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    use EnforcesTenantAccess;

    public function index(Request $request)
    {
        $menus = $this->branchScoped($request, Menu::query())->with('categories','branch')->get();
        return response()->json(['data' => $menus]);
    }

    public function show(Request $request, $id)
    {
        $menu = $this->branchScoped($request, Menu::with('categories','branch'))->findOrFail($id);
        return response()->json(['data' => $menu]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'required|exists:branches,id',
            'categories' => 'array',
            'categories.*' => 'exists:categories,id',
        ]);
        $data['branch_id'] = $this->branchIdForWrite($request, (int) $data['branch_id']);
        $this->ensureCategoriesBelongToBranch($request, $data['categories'] ?? [], (int) $data['branch_id']);
        $menu = Menu::create([
            'name' => $data['name'],
            'branch_id' => $data['branch_id'],
        ]);
        if (!empty($data['categories'])) {
            $menu->categories()->sync($data['categories']);
        }
        return response()->json(['data' => $menu->load('categories')], 201);
    }

    public function update(Request $request, $id)
    {
        $menu = $this->branchScoped($request, Menu::query())->findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'required|exists:branches,id',
            'categories' => 'array',
            'categories.*' => 'exists:categories,id',
        ]);
        $data['branch_id'] = $this->branchIdForWrite($request, (int) $data['branch_id']);
        $this->ensureCategoriesBelongToBranch($request, $data['categories'] ?? [], (int) $data['branch_id']);
        $menu->update([
            'name' => $data['name'],
            'branch_id' => $data['branch_id'],
        ]);
        if (isset($data['categories'])) {
            $menu->categories()->sync($data['categories']);
        }
        return response()->json(['data' => $menu->load('categories')]);
    }

    public function destroy(Request $request, $id)
    {
        $menu = $this->branchScoped($request, Menu::query())->findOrFail($id);
        $menu->categories()->detach();
        $menu->delete();
        return response()->json(['success' => true]);
    }

    private function ensureCategoriesBelongToBranch(Request $request, array $categoryIds, int $branchId): void
    {
        if (!$categoryIds) {
            return;
        }

        $count = Category::query()
            ->whereIn('id', $categoryIds)
            ->where('branch_id', $branchId)
            ->count();

        abort_unless($count === count(array_unique($categoryIds)), 422, 'Menu categories must belong to the selected branch.');
        $this->ensureBranchAccess($request, $branchId);
    }
}
