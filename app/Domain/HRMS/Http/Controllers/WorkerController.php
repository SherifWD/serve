<?php

namespace App\Domain\HRMS\Http\Controllers;

use App\Domain\HRMS\Http\Requests\WorkerStoreRequest;
use App\Domain\HRMS\Http\Requests\WorkerUpdateRequest;
use App\Domain\HRMS\Http\Resources\WorkerResource;
use App\Domain\HRMS\Models\Worker;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $workers = Worker::query()
            ->with('contracts')
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($q, $status) => $q->where('employment_status', $status))
            ->orderBy('last_name')
            ->paginate($request->integer('per_page', 20));

        return WorkerResource::collection($workers)->response();
    }

    public function store(WorkerStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $worker = Worker::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return WorkerResource::make($worker)->response()->setStatusCode(201);
    }

    public function show(Worker $worker): JsonResponse
    {
        $this->authorizeTenantResource($worker);

        return WorkerResource::make($worker->load('contracts'))->response();
    }

    public function update(WorkerUpdateRequest $request, Worker $worker): JsonResponse
    {
        $this->authorizeTenantResource($worker);

        $worker->update($request->validated());

        return WorkerResource::make($worker->load('contracts'))->response();
    }

    public function destroy(Worker $worker): JsonResponse
    {
        $this->authorizeTenantResource($worker);
        $worker->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Worker $worker): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($worker->tenant_id !== $tenantId, 404);
    }
}

