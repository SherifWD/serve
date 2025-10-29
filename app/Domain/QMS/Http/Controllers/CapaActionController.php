<?php

namespace App\Domain\QMS\Http\Controllers;

use App\Domain\QMS\Http\Requests\CapaActionStoreRequest;
use App\Domain\QMS\Http\Requests\CapaActionUpdateRequest;
use App\Domain\QMS\Http\Resources\CapaActionResource;
use App\Domain\QMS\Models\CapaAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CapaActionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $actions = CapaAction::query()
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('non_conformity_id'), fn ($q, $ncId) => $q->where('non_conformity_id', $ncId))
            ->orderByDesc('due_at')
            ->paginate($request->integer('per_page', 20));

        return CapaActionResource::collection($actions)->response();
    }

    public function store(CapaActionStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $action = CapaAction::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return CapaActionResource::make($action)->response()->setStatusCode(201);
    }

    public function show(CapaAction $capaAction): JsonResponse
    {
        $this->authorizeTenantResource($capaAction);

        return CapaActionResource::make($capaAction)->response();
    }

    public function update(CapaActionUpdateRequest $request, CapaAction $capaAction): JsonResponse
    {
        $this->authorizeTenantResource($capaAction);

        $capaAction->update($request->validated());

        return CapaActionResource::make($capaAction)->response();
    }

    public function destroy(CapaAction $capaAction): JsonResponse
    {
        $this->authorizeTenantResource($capaAction);
        $capaAction->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(CapaAction $capaAction): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($capaAction->tenant_id !== $tenantId, 404);
    }
}

