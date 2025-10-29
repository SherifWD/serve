<?php

namespace App\Domain\HRMS\Http\Controllers;

use App\Domain\HRMS\Http\Requests\LeaveRequestStoreRequest;
use App\Domain\HRMS\Http\Requests\LeaveRequestUpdateRequest;
use App\Domain\HRMS\Http\Resources\LeaveRequestResource;
use App\Domain\HRMS\Models\LeaveRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $requests = LeaveRequest::query()
            ->with('worker')
            ->where('tenant_id', $tenantId)
            ->when($request->query('worker_id'), fn ($q, $workerId) => $q->where('worker_id', $workerId))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('start_date')
            ->paginate($request->integer('per_page', 20));

        return LeaveRequestResource::collection($requests)->response();
    }

    public function store(LeaveRequestStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $leaveRequest = LeaveRequest::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return LeaveRequestResource::make($leaveRequest->load('worker'))->response()->setStatusCode(201);
    }

    public function show(LeaveRequest $leaveRequest): JsonResponse
    {
        $this->authorizeTenantResource($leaveRequest);

        return LeaveRequestResource::make($leaveRequest->load('worker'))->response();
    }

    public function update(LeaveRequestUpdateRequest $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $this->authorizeTenantResource($leaveRequest);

        $leaveRequest->update($request->validated());

        return LeaveRequestResource::make($leaveRequest->load('worker'))->response();
    }

    public function destroy(LeaveRequest $leaveRequest): JsonResponse
    {
        $this->authorizeTenantResource($leaveRequest);
        $leaveRequest->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(LeaveRequest $leaveRequest): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($leaveRequest->tenant_id !== $tenantId, 404);
    }
}

