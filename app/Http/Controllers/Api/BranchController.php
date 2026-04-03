<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Branch::query()
            ->with('restaurant:id,name,kind')
            ->orderBy('name');

        if ($user->isPlatformAdmin()) {
            if ($request->filled('restaurant_id')) {
                $query->where('restaurant_id', $request->integer('restaurant_id'));
            }

            return $query->get();
        }

        if ($user->restaurant_id) {
            return $query->where('restaurant_id', $user->restaurant_id)->get();
        }

        return $user->branch_id
            ? $query->whereKey($user->branch_id)->get()
            : collect();
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'restaurant_id' => 'nullable|integer|exists:restaurants,id',
        ]);

        if ($user->isPlatformAdmin()) {
            if (empty($data['restaurant_id'])) {
                throw ValidationException::withMessages([
                    'restaurant_id' => 'Restaurant is required for a new branch.',
                ]);
            }
        } elseif ($user->restaurant_id) {
            $data['restaurant_id'] = $user->restaurant_id;
        } else {
            abort(403, 'You cannot create branches.');
        }

        return Branch::create($data)->load('restaurant:id,name,kind');
    }

    public function show(Request $request, Branch $branch)
    {
        $this->ensureBranchAccess($request->user(), $branch);

        return $branch->load('restaurant:id,name,kind');
    }

    public function update(Request $request, Branch $branch)
    {
        $user = $request->user();
        $this->ensureBranchAccess($user, $branch);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'restaurant_id' => 'nullable|integer|exists:restaurants,id',
        ]);

        if (!$user->isPlatformAdmin()) {
            $data['restaurant_id'] = $branch->restaurant_id;
        }

        $branch->update($data);

        return $branch->fresh('restaurant:id,name,kind');
    }

    public function destroy(Request $request, Branch $branch)
    {
        $this->ensureBranchAccess($request->user(), $branch);
        $branch->delete();

        return response()->json(['message' => 'Branch deleted']);
    }

    private function ensureBranchAccess($user, Branch $branch): void
    {
        if ($user->isPlatformAdmin()) {
            return;
        }

        if ($user->restaurant_id && (int) $user->restaurant_id === (int) $branch->restaurant_id) {
            return;
        }

        abort(403, 'You cannot manage this branch.');
    }
}
