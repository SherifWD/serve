<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\ClientMutation;
use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientMutationIdempotency
{
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        $clientMutationId = $this->clientMutationId($request);
        if (!$clientMutationId) {
            return $next($request);
        }

        $requestHash = $this->requestHash($request);
        $existing = ClientMutation::query()
            ->where('client_mutation_id', $clientMutationId)
            ->first();

        if ($existing) {
            if (!hash_equals($existing->request_hash, $requestHash)) {
                return response()->json([
                    'message' => 'Client mutation id was already used for a different request.',
                    'client_mutation_id' => $clientMutationId,
                ], 409);
            }

            if (in_array($existing->status, ['succeeded', 'failed'], true) && $existing->response_payload !== null) {
                return response()
                    ->json($existing->response_payload, $existing->http_status ?: 200)
                    ->header('X-Client-Mutation-Replayed', 'true');
            }

            if ($existing->updated_at && $existing->updated_at->gt(now()->subMinute())) {
                return response()->json([
                    'message' => 'Client mutation is already being processed.',
                    'client_mutation_id' => $clientMutationId,
                ], 409);
            }

            $mutation = $existing;
            $mutation->forceFill([
                'status' => 'processing',
                'last_seen_at' => now(),
            ])->save();
        } else {
            $mutation = ClientMutation::query()->create([
                'client_mutation_id' => $clientMutationId,
                'user_id' => $request->user()?->id,
                'restaurant_id' => $this->restaurantId($request),
                'branch_id' => $this->branchId($request),
                'action' => $this->actionName($request),
                'request_hash' => $requestHash,
                'status' => 'processing',
                'first_seen_at' => now(),
                'last_seen_at' => now(),
            ]);
        }

        $response = $next($request);
        $httpStatus = $response->getStatusCode();
        $payload = $response instanceof JsonResponse
            ? $response->getData(true)
            : ['message' => 'Mutation completed with a non-json response.'];

        $mutation->forceFill([
            'status' => $httpStatus < 400 ? 'succeeded' : 'failed',
            'http_status' => $httpStatus,
            'response_payload' => $payload,
            'error_message' => $httpStatus < 400 ? null : ($payload['message'] ?? $payload['error'] ?? null),
            'last_seen_at' => now(),
        ])->save();

        $response->headers->set('X-Client-Mutation-Id', $clientMutationId);

        return $response;
    }

    private function clientMutationId(Request $request): ?string
    {
        $value = $request->header('X-Client-Mutation-Id') ?: $request->input('client_mutation_id');
        $value = is_string($value) ? trim($value) : null;

        return $value !== '' ? $value : null;
    }

    private function requestHash(Request $request): string
    {
        $payload = Arr::except($request->all(), ['client_mutation_id']);
        ksort($payload);

        return hash('sha256', json_encode([
            'method' => $request->method(),
            'path' => $request->path(),
            'payload' => $payload,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function actionName(Request $request): string
    {
        return str($request->path())
            ->after('api/mobile/')
            ->replaceMatches('/[0-9]+/', '{id}')
            ->replace('/', '.')
            ->limit(100, '')
            ->toString();
    }

    private function branchId(Request $request): ?int
    {
        $user = $request->user();
        if ($user instanceof User && $user->branch_id) {
            return (int) $user->branch_id;
        }

        return $request->filled('branch_id') ? $request->integer('branch_id') : null;
    }

    private function restaurantId(Request $request): ?int
    {
        $user = $request->user();
        if ($user instanceof User && $user->restaurant_id) {
            return (int) $user->restaurant_id;
        }

        $branchId = $this->branchId($request);
        if ($branchId) {
            return Branch::query()->whereKey($branchId)->value('restaurant_id');
        }

        return null;
    }
}
