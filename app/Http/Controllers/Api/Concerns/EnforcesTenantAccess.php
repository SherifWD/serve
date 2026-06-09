<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

trait EnforcesTenantAccess
{
    protected function branchScoped(Request $request, Builder $query, string $branchColumn = 'branch_id'): Builder
    {
        $user = $this->apiUser($request);

        if ($user->isPlatformAdmin()) {
            if ($request->filled('restaurant_id')) {
                $restaurantId = $request->integer('restaurant_id');
                $query->whereHas('branch', fn (Builder $branchQuery) => $branchQuery->where('restaurant_id', $restaurantId));
            }

            if ($request->filled('branch_id')) {
                $query->where($branchColumn, $request->integer('branch_id'));
            }

            return $query;
        }

        if ($user->branch_id) {
            return $query->where($branchColumn, $user->branch_id);
        }

        if ($user->restaurant_id) {
            return $query->whereHas('branch', fn (Builder $branchQuery) => $branchQuery->where('restaurant_id', $user->restaurant_id));
        }

        return $query->whereRaw('1 = 0');
    }

    protected function branchRelationScoped(Request $request, Builder $query, string $relation = 'branch'): Builder
    {
        $user = $this->apiUser($request);

        if ($user->isPlatformAdmin()) {
            if ($request->filled('restaurant_id')) {
                $restaurantId = $request->integer('restaurant_id');
                $query->whereHas($relation, fn (Builder $branchQuery) => $branchQuery->where('restaurant_id', $restaurantId));
            }

            if ($request->filled('branch_id')) {
                $branchId = $request->integer('branch_id');
                $query->whereHas($relation, fn (Builder $branchQuery) => $branchQuery->whereKey($branchId));
            }

            return $query;
        }

        if ($user->branch_id) {
            return $query->whereHas($relation, fn (Builder $branchQuery) => $branchQuery->whereKey($user->branch_id));
        }

        if ($user->restaurant_id) {
            return $query->whereHas($relation, fn (Builder $branchQuery) => $branchQuery->where('restaurant_id', $user->restaurant_id));
        }

        return $query->whereRaw('1 = 0');
    }

    protected function restaurantScoped(Request $request, Builder $query, string $restaurantColumn = 'restaurant_id'): Builder
    {
        $user = $this->apiUser($request);

        if ($user->isPlatformAdmin()) {
            if ($request->filled('restaurant_id')) {
                $query->where($restaurantColumn, $request->integer('restaurant_id'));
            }

            return $query;
        }

        if ($user->restaurant_id) {
            return $query->where($restaurantColumn, $user->restaurant_id);
        }

        if ($user->branch_id) {
            $restaurantId = Branch::query()->whereKey($user->branch_id)->value('restaurant_id');
            return $restaurantId
                ? $query->where($restaurantColumn, $restaurantId)
                : $query->whereRaw('1 = 0');
        }

        return $query->whereRaw('1 = 0');
    }

    protected function branchIdForWrite(Request $request, ?int $branchId): int
    {
        $user = $this->apiUser($request);

        if ($user->branch_id) {
            if ($branchId !== null && (int) $branchId !== (int) $user->branch_id) {
                abort(403, 'You cannot write outside your branch.');
            }

            return (int) $user->branch_id;
        }

        if ($branchId === null) {
            throw ValidationException::withMessages([
                'branch_id' => 'Branch is required.',
            ]);
        }

        $this->ensureBranchAccess($request, $branchId);

        return (int) $branchId;
    }

    protected function defaultBranchIdForWrite(Request $request, ?int $branchId): int
    {
        if ($branchId !== null) {
            return $this->branchIdForWrite($request, $branchId);
        }

        $user = $this->apiUser($request);

        if ($user->branch_id) {
            return (int) $user->branch_id;
        }

        if ($user->restaurant_id) {
            $defaultBranchId = Branch::query()
                ->where('restaurant_id', $user->restaurant_id)
                ->orderBy('id')
                ->value('id');

            if ($defaultBranchId) {
                return (int) $defaultBranchId;
            }
        }

        throw ValidationException::withMessages([
            'branch_id' => 'Branch is required.',
        ]);
    }

    protected function branchIdsForWrite(Request $request, ?array $branchIds = null, ?int $branchId = null): array
    {
        $ids = collect($branchIds ?? [])
            ->merge($branchId !== null ? [$branchId] : [])
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $user = $this->apiUser($request);
        if ($ids->isEmpty() && $user->branch_id) {
            $ids = collect([(int) $user->branch_id]);
        }

        if ($ids->isEmpty()) {
            throw ValidationException::withMessages([
                'branch_ids' => 'At least one branch is required.',
            ]);
        }

        return $ids
            ->map(fn (int $id) => $this->branchIdForWrite($request, $id))
            ->unique()
            ->values()
            ->all();
    }

    protected function restaurantBranchIdsForWrite(Request $request, ?array $branchIds = null, ?int $restaurantId = null): array
    {
        if ($branchIds) {
            return $this->branchIdsForWrite($request, $branchIds);
        }

        $user = $this->apiUser($request);
        $query = Branch::query()->orderBy('id');

        if ($user->branch_id) {
            return [(int) $user->branch_id];
        }

        if ($user->restaurant_id) {
            $query->where('restaurant_id', $user->restaurant_id);
        } elseif ($restaurantId !== null) {
            $this->ensureRestaurantAccess($request, $restaurantId);
            $query->where('restaurant_id', $restaurantId);
        } elseif (! $user->isPlatformAdmin()) {
            return [];
        } else {
            throw ValidationException::withMessages([
                'restaurant_id' => 'Restaurant is required when distributing across all branches.',
            ]);
        }

        $ids = $query->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($ids as $id) {
            $this->ensureBranchAccess($request, $id);
        }

        return $ids;
    }

    protected function restaurantIdForWrite(Request $request, ?int $restaurantId): int
    {
        $user = $this->apiUser($request);

        if ($user->isPlatformAdmin()) {
            if ($restaurantId === null) {
                throw ValidationException::withMessages([
                    'restaurant_id' => 'Restaurant is required.',
                ]);
            }

            return (int) $restaurantId;
        }

        if ($user->restaurant_id) {
            if ($restaurantId !== null && (int) $restaurantId !== (int) $user->restaurant_id) {
                abort(403, 'You cannot write outside your restaurant.');
            }

            return (int) $user->restaurant_id;
        }

        if ($user->branch_id) {
            $resolved = Branch::query()->whereKey($user->branch_id)->value('restaurant_id');
            if ($resolved) {
                return (int) $resolved;
            }
        }

        abort(403, 'You cannot write restaurant-level records.');
    }

    protected function ensureBranchAccess(Request $request, ?int $branchId): void
    {
        $user = $this->apiUser($request);

        if ($branchId === null) {
            if ($user->isPlatformAdmin()) {
                return;
            }

            abort(403, 'Branch-scoped record is missing a branch.');
        }

        if ($user->isPlatformAdmin()) {
            return;
        }

        if ($user->branch_id && (int) $user->branch_id === (int) $branchId) {
            return;
        }

        if ($user->restaurant_id) {
            $allowed = Branch::query()
                ->whereKey($branchId)
                ->where('restaurant_id', $user->restaurant_id)
                ->exists();

            if ($allowed) {
                return;
            }
        }

        abort(403, 'You cannot access this branch.');
    }

    protected function ensureRestaurantAccess(Request $request, ?int $restaurantId): void
    {
        $user = $this->apiUser($request);

        if ($restaurantId === null) {
            abort(403, 'Restaurant-scoped record is missing a restaurant.');
        }

        if ($user->isPlatformAdmin() || (int) $user->restaurant_id === (int) $restaurantId) {
            return;
        }

        if ($user->branch_id) {
            $allowed = Branch::query()
                ->whereKey($user->branch_id)
                ->where('restaurant_id', $restaurantId)
                ->exists();

            if ($allowed) {
                return;
            }
        }

        abort(403, 'You cannot access this restaurant.');
    }

    protected function accessibleBranchIds(Request $request): ?array
    {
        $user = $this->apiUser($request);

        if ($user->isPlatformAdmin()) {
            return null;
        }

        if ($user->branch_id) {
            return [(int) $user->branch_id];
        }

        if ($user->restaurant_id) {
            return Branch::query()
                ->where('restaurant_id', $user->restaurant_id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return [];
    }

    private function apiUser(Request $request): User
    {
        $user = $request->user();

        if (!$user instanceof User) {
            abort(401, 'Unauthorized');
        }

        return $user;
    }
}
