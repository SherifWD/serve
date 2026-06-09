<?php

use App\Models\Branch;
use Illuminate\Support\Facades\Broadcast;

// branch-wide channels (waiters & KDS in same branch)
Broadcast::channel('branch.{branchId}', function ($user, $branchId) {
    if ((int) $user->branch_id === (int) $branchId) {
        return ['id' => $user->id, 'name' => $user->name];
    }

    if ($user->restaurant_id) {
        $allowed = Branch::query()
            ->whereKey($branchId)
            ->where('restaurant_id', $user->restaurant_id)
            ->exists();

        return $allowed ? ['id' => $user->id, 'name' => $user->name] : false;
    }

    return false;
});
