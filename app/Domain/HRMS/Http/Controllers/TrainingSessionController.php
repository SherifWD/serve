<?php

namespace App\Domain\HRMS\Http\Controllers;

use App\Domain\HRMS\Http\Requests\TrainingSessionStoreRequest;
use App\Domain\HRMS\Http\Requests\TrainingSessionUpdateRequest;
use App\Domain\HRMS\Http\Resources\TrainingSessionResource;
use App\Domain\HRMS\Models\TrainingSession;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainingSessionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $sessions = TrainingSession::query()
            ->with('assignments.worker')
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('scheduled_date')
            ->paginate($request->integer('per_page', 20));

        return TrainingSessionResource::collection($sessions)->response();
    }

    public function store(TrainingSessionStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $session = TrainingSession::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return TrainingSessionResource::make($session)->response()->setStatusCode(201);
    }

    public function show(TrainingSession $trainingSession): JsonResponse
    {
        $this->authorizeTenantResource($trainingSession);

        return TrainingSessionResource::make($trainingSession->load('assignments.worker'))->response();
    }

    public function update(TrainingSessionUpdateRequest $request, TrainingSession $trainingSession): JsonResponse
    {
        $this->authorizeTenantResource($trainingSession);

        $trainingSession->update($request->validated());

        return TrainingSessionResource::make($trainingSession->load('assignments.worker'))->response();
    }

    public function destroy(TrainingSession $trainingSession): JsonResponse
    {
        $this->authorizeTenantResource($trainingSession);
        $trainingSession->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(TrainingSession $trainingSession): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($trainingSession->tenant_id !== $tenantId, 404);
    }
}

