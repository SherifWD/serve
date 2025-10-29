<?php

namespace App\Domain\HRMS\Http\Controllers;

use App\Domain\HRMS\Http\Requests\TrainingAssignmentStoreRequest;
use App\Domain\HRMS\Http\Requests\TrainingAssignmentUpdateRequest;
use App\Domain\HRMS\Http\Resources\TrainingAssignmentResource;
use App\Domain\HRMS\Models\TrainingAssignment;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainingAssignmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $assignments = TrainingAssignment::query()
            ->with('worker')
            ->where('tenant_id', $tenantId)
            ->when($request->query('training_session_id'), fn ($q, $sessionId) => $q->where('training_session_id', $sessionId))
            ->when($request->query('worker_id'), fn ($q, $workerId) => $q->where('worker_id', $workerId))
            ->paginate($request->integer('per_page', 20));

        return TrainingAssignmentResource::collection($assignments)->response();
    }

    public function store(TrainingAssignmentStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $assignment = TrainingAssignment::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return TrainingAssignmentResource::make($assignment->load('worker'))->response()->setStatusCode(201);
    }

    public function show(TrainingAssignment $trainingAssignment): JsonResponse
    {
        $this->authorizeTenantResource($trainingAssignment);

        return TrainingAssignmentResource::make($trainingAssignment->load('worker'))->response();
    }

    public function update(TrainingAssignmentUpdateRequest $request, TrainingAssignment $trainingAssignment): JsonResponse
    {
        $this->authorizeTenantResource($trainingAssignment);

        $trainingAssignment->update($request->validated());

        return TrainingAssignmentResource::make($trainingAssignment->load('worker'))->response();
    }

    public function destroy(TrainingAssignment $trainingAssignment): JsonResponse
    {
        $this->authorizeTenantResource($trainingAssignment);
        $trainingAssignment->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(TrainingAssignment $trainingAssignment): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($trainingAssignment->tenant_id !== $tenantId, 404);
    }
}

