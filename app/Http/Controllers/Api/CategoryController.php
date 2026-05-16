<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    use EnforcesTenantAccess;

    public function index(Request $request)
    {
        $categories = $this->categoryQueryForUser($request)
            ->with('branch.restaurant:id,name,kind')
            ->orderBy('name')
            ->get()
            ->unique(fn (Category $category) => mb_strtolower(trim($category->name)))
            ->values();

        return response()->json(['data' => $categories]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'nullable|integer|exists:branches,id',
        ]);

        $data['name'] = trim($data['name']);
        if (!empty($data['branch_id'])) {
            $data['branch_id'] = $this->branchIdForWrite($request, (int) $data['branch_id']);
        } else {
            $data['branch_id'] = null;
        }

        $user = $request->user();
        $existingQuery = $user?->isPlatformAdmin()
            ? Category::query()
            : $this->categoryQueryForUser($request);

        $existing = $existingQuery
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($data['name'])])
            ->first();

        if ($existing) {
            return response()->json($existing->load('branch.restaurant:id,name,kind'));
        }

        $category = Category::create($data);
        return response()->json($category->load('branch.restaurant:id,name,kind'), 201);
    }

    public function show(Request $request, $id)
    {
        return $this->categoryQueryForUser($request)
            ->with('branch.restaurant:id,name,kind')
            ->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $category = $this->categoryQueryForUser($request)->findOrFail($id);
        $data = $request->validate([
            'name' => 'string|max:255',
            'branch_id' => 'nullable|integer|exists:branches,id',
        ]);
        if (isset($data['name'])) {
            $data['name'] = trim($data['name']);
            $duplicate = Category::query()
                ->whereKeyNot($category->id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($data['name'])])
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'name' => 'Category names must be unique.',
                ]);
            }
        }

        if (array_key_exists('branch_id', $data)) {
            $data['branch_id'] = $data['branch_id'] === null
                ? null
                : $this->branchIdForWrite($request, (int) $data['branch_id']);
        }
        $category->update($data);
        return response()->json($category->load('branch.restaurant:id,name,kind'));
    }

    public function destroy(Request $request, $id)
    {
        $category = $this->categoryQueryForUser($request)->findOrFail($id);
        $category->delete();
        return response()->json(['message' => 'Deleted']);
    }

    private function categoryQueryForUser(Request $request)
    {
        $query = Category::query();
        $user = $request->user();

        if ($user?->isPlatformAdmin()) {
            if ($request->filled('branch_id')) {
                $query->where('branch_id', $request->integer('branch_id'));
            } elseif ($request->filled('restaurant_id')) {
                $restaurantId = $request->integer('restaurant_id');
                $query->where(function ($scope) use ($restaurantId) {
                    $scope->whereNull('branch_id')
                        ->orWhereHas('branch', fn ($branchQuery) => $branchQuery->where('restaurant_id', $restaurantId));
                });
            }

            return $query;
        }

        if ($user?->branch_id) {
            return $query->where(function ($scope) use ($user) {
                $scope->whereNull('branch_id')
                    ->orWhere('branch_id', $user->branch_id);
            });
        }

        if ($user?->restaurant_id) {
            return $query->where(function ($scope) use ($user) {
                $scope->whereNull('branch_id')
                    ->orWhereHas('branch', fn ($branchQuery) => $branchQuery->where('restaurant_id', $user->restaurant_id));
            });
        }

        return $query->whereRaw('1 = 0');
    }
}
