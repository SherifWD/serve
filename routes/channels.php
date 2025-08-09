<?php
use Illuminate\Support\Facades\Broadcast;

// branch-wide channels (waiters & KDS in same branch)
Broadcast::channel('branch.{branchId}', function ($user, $branchId) {
    return (int)$user->branch_id === (int)$branchId
        ? ['id' => $user->id, 'name' => $user->name]
        : false;
});