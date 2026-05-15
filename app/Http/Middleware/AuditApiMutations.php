<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuditApiMutations
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldAudit($request, $response)) {
            try {
                /** @var User|null $user */
                $user = $request->user();

                AuditLog::query()->create([
                    'model_type' => 'api_request',
                    'model_id' => 0,
                    'user_id' => $user?->id,
                    'changes' => [
                        'method' => $request->method(),
                        'path' => $request->path(),
                        'route' => $request->route()?->getName(),
                        'status' => $response->getStatusCode(),
                        'ip' => $request->ip(),
                        'user_agent' => substr((string) $request->userAgent(), 0, 255),
                        'branch_id' => $user?->branch_id,
                        'restaurant_id' => $user?->restaurant_id,
                        'client_mutation_id' => $request->header('X-Client-Mutation-Id') ?: $request->input('client_mutation_id'),
                    ],
                ]);
            } catch (\Throwable $exception) {
                Log::warning('Failed to write API audit log', [
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $response;
    }

    private function shouldAudit(Request $request, Response $response): bool
    {
        if (!str_starts_with($request->path(), 'api/')) {
            return false;
        }

        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return false;
        }

        if ($response->getStatusCode() >= 500) {
            return false;
        }

        return $request->user() instanceof User;
    }
}
