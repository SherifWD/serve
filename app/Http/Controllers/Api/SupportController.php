<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ClientMutation;
use App\Models\EtaReceiptSubmission;
use App\Models\Device;
use App\Models\PaymentAttempt;
use App\Models\PrintJob;
use App\Models\RestaurantSubscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupportController extends Controller
{
    use EnforcesTenantAccess;

    public function health()
    {
        try {
            DB::select('select 1');
            $database = 'ok';
        } catch (\Throwable) {
            $database = 'error';
        }

        return response()->json([
            'status' => $database === 'ok' ? 'ok' : 'degraded',
            'database' => $database,
            'queue_connection' => config('queue.default'),
            'cache_store' => config('cache.default'),
            'broadcast_connection' => config('broadcasting.default'),
            'environment' => app()->environment(),
        ], $database === 'ok' ? 200 : 503);
    }

    public function context(Request $request)
    {
        $user = $request->user();
        $restaurantId = $this->restaurantIdForContext($user);
        $subscription = $restaurantId
            ? RestaurantSubscription::query()
                ->with('plan')
                ->where('restaurant_id', $restaurantId)
                ->latest('id')
                ->first()
            : null;

        return response()->json([
            'user' => [
                'id' => $user?->id,
                'email' => $user?->email,
                'role' => $user?->role,
                'branch_id' => $user?->branch_id,
                'restaurant_id' => $user?->restaurant_id,
            ],
            'tenant' => [
                'branch' => $user?->branch,
                'restaurant' => $user?->restaurant,
            ],
            'subscription' => $subscription ? [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'current_period_ends_at' => $subscription->current_period_ends_at?->toISOString(),
                'plan' => $subscription->plan,
            ] : null,
            'runtime' => [
                'php' => PHP_VERSION,
                'environment' => app()->environment(),
                'queue_connection' => config('queue.default'),
                'cache_store' => config('cache.default'),
                'broadcast_connection' => config('broadcasting.default'),
            ],
        ]);
    }

    public function ops(Request $request)
    {
        $branchIds = $this->accessibleBranchIds($request);
        $restaurantId = $this->restaurantIdForContext($request->user());
        $branchScope = fn ($query) => $branchIds === null ? $query : $query->whereIn('branch_id', $branchIds);
        $restaurantScope = fn ($query) => $restaurantId ? $query->where('restaurant_id', $restaurantId) : $query;

        $devices = $branchScope(Device::query()->where('is_active', true));
        $onlineDeviceCount = (clone $devices)->where('last_seen_at', '>=', now()->subMinutes(5))->count();
        $activeDeviceCount = (clone $devices)->count();

        return response()->json([
            'server_time' => now()->toISOString(),
            'scope' => [
                'restaurant_id' => $restaurantId,
                'branch_ids' => $branchIds,
            ],
            'devices' => [
                'active' => $activeDeviceCount,
                'online' => $onlineDeviceCount,
                'offline' => max($activeDeviceCount - $onlineDeviceCount, 0),
            ],
            'print_jobs' => [
                'queued' => $branchScope(PrintJob::query())->where('status', 'queued')->count(),
                'printing' => $branchScope(PrintJob::query())->where('status', 'printing')->count(),
                'failed' => $branchScope(PrintJob::query())->where('status', 'failed')->count(),
            ],
            'payments' => [
                'requires_action' => $branchScope(PaymentAttempt::query())->where('status', 'requires_action')->count(),
                'approved' => $branchScope(PaymentAttempt::query())->where('status', 'approved')->count(),
                'captured_today' => $branchScope(PaymentAttempt::query())
                    ->where('status', 'captured')
                    ->where('captured_at', '>=', now()->startOfDay())
                    ->count(),
                'failed_today' => $branchScope(PaymentAttempt::query())
                    ->where('status', 'failed')
                    ->where('updated_at', '>=', now()->startOfDay())
                    ->count(),
            ],
            'offline_mutations' => [
                'processing' => $branchScope(ClientMutation::query())->where('status', 'processing')->count(),
                'failed_today' => $branchScope(ClientMutation::query())
                    ->where('status', 'failed')
                    ->where('updated_at', '>=', now()->startOfDay())
                    ->count(),
            ],
            'eta' => [
                'queued' => $restaurantScope(EtaReceiptSubmission::query())->where('status', 'queued')->count(),
                'failed' => $restaurantScope(EtaReceiptSubmission::query())->where('status', 'failed')->count(),
            ],
            'readiness' => [
                'queue_connection' => config('queue.default'),
                'cache_store' => config('cache.default'),
                'broadcast_connection' => config('broadcasting.default'),
                'database' => $this->databaseStatus(),
            ],
        ]);
    }

    private function restaurantIdForContext($user): ?int
    {
        if (!$user instanceof User) {
            return null;
        }

        if ($user->restaurant_id) {
            return (int) $user->restaurant_id;
        }

        if ($user->branch_id) {
            return Branch::query()->whereKey($user->branch_id)->value('restaurant_id');
        }

        return null;
    }

    private function databaseStatus(): string
    {
        try {
            DB::select('select 1');
            return 'ok';
        } catch (\Throwable) {
            return 'error';
        }
    }
}
