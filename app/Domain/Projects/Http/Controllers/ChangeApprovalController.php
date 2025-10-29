<?php

namespace App\Domain\Projects\Http\Controllers;

use App\Domain\Projects\Http\Requests\ChangeApprovalStoreRequest;
use App\Domain\Projects\Http\Requests\ChangeApprovalUpdateRequest;
use App\Domain\Projects\Http\Resources\ChangeApprovalResource;
use App\Domain\Projects\Models\ChangeApproval;
use App\Domain\Projects\Models\ChangeRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChangeApprovalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $approvals = ChangeApproval::query()
            ->with(['changeRequest', 'approver'])
            ->where('tenant_id', $tenantId)
            ->when($request->query('change_request_id'), fn ($query, $id) => $query->where('change_request_id', $id))
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 25));

        return ChangeApprovalResource::collection($approvals)->response();
    }

    public function store(ChangeApprovalStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $data = $request->validated();

        ChangeRequest::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $data['change_request_id'])
            ->firstOrFail();

        $approval = ChangeApproval::create([
            ...$data,
            'tenant_id' => $tenantId,
        ])->load(['changeRequest', 'approver']);

        return ChangeApprovalResource::make($approval)->response()->setStatusCode(201);
    }

    public function show(ChangeApproval $changeApproval): JsonResponse
    {
        $this->authorizeTenantResource($changeApproval);

        return ChangeApprovalResource::make($changeApproval->load(['changeRequest', 'approver']))->response();
    }

    public function update(ChangeApprovalUpdateRequest $request, ChangeApproval $changeApproval): JsonResponse
    {
        $this->authorizeTenantResource($changeApproval);
        $data = $request->validated();
        $tenantId = app('tenant.context')->ensureTenant()->id;

        if (isset($data['change_request_id'])) {
            ChangeRequest::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $data['change_request_id'])
                ->firstOrFail();
        }

        $changeApproval->update($data);

        return ChangeApprovalResource::make($changeApproval->load(['changeRequest', 'approver']))->response();
    }

    public function destroy(ChangeApproval $changeApproval): JsonResponse
    {
        $this->authorizeTenantResource($changeApproval);
        $changeApproval->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(ChangeApproval $changeApproval): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($changeApproval->tenant_id !== $tenantId, 404);
    }
}
