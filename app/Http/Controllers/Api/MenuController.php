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
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
        ]);
        $data['branch_id'] = $this->branchIdForWrite($request, (int) $data['branch_id']);
        $categoryIds = $this->categoryIdsForUse($request, $data['categories'] ?? [], (int) $data['branch_id']);
        $menu = Menu::create([
            'name' => $data['name'],
            'branch_id' => $data['branch_id'],
        ]);
        if ($categoryIds) {
            $menu->categories()->sync($categoryIds);
        }
        return response()->json(['data' => $menu->load('categories')], 201);
    }

    public function update(Request $request, $id)
    {
        $menu = $this->branchScoped($request, Menu::query())->findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'required|exists:branches,id',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
        ]);
        $data['branch_id'] = $this->branchIdForWrite($request, (int) $data['branch_id']);
        $categoryIds = $this->categoryIdsForUse($request, $data['categories'] ?? [], (int) $data['branch_id']);
        $menu->update([
            'name' => $data['name'],
            'branch_id' => $data['branch_id'],
        ]);
        if (isset($data['categories'])) {
            $menu->categories()->sync($categoryIds);
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

    private function categoryIdsForUse(Request $request, array $categoryIds, int $branchId): array
    {
        $categoryIds = collect($categoryIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (!$categoryIds) {
            return [];
        }

        $this->ensureBranchAccess($request, $branchId);

        $query = Category::query()
            ->whereIn('id', $categoryIds);

        $user = $request->user();
        if (!$user?->isPlatformAdmin()) {
            if ($user?->branch_id) {
                $query->where(function ($scope) use ($user) {
                    $scope->whereNull('branch_id')
                        ->orWhere('branch_id', $user->branch_id);
                });
            } elseif ($user?->restaurant_id) {
                $query->where(function ($scope) use ($user) {
                    $scope->whereNull('branch_id')
                        ->orWhereHas('branch', fn ($branchQuery) => $branchQuery->where('restaurant_id', $user->restaurant_id));
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $count = $query->count();

        abort_unless($count === count($categoryIds), 422, 'Menu categories must be available to the selected restaurant.');

        return $categoryIds;
    }
}
