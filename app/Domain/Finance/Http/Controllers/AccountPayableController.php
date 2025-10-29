<?php

namespace App\Domain\Finance\Http\Controllers;

use App\Domain\Finance\Http\Requests\AccountPayableStoreRequest;
use App\Domain\Finance\Http\Requests\AccountPayableUpdateRequest;
use App\Domain\Finance\Http\Resources\AccountPayableResource;
use App\Domain\Finance\Models\AccountPayable;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountPayableController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $records = AccountPayable::query()
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('invoice_date')
            ->paginate($request->integer('per_page', 20));

        return AccountPayableResource::collection($records)->response();
    }

    public function store(AccountPayableStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $record = AccountPayable::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return AccountPayableResource::make($record)->response()->setStatusCode(201);
    }

    public function show(AccountPayable $accountPayable): JsonResponse
    {
        $this->authorizeTenantResource($accountPayable);

        return AccountPayableResource::make($accountPayable)->response();
    }

    public function update(AccountPayableUpdateRequest $request, AccountPayable $accountPayable): JsonResponse
    {
        $this->authorizeTenantResource($accountPayable);

        $accountPayable->update($request->validated());

        return AccountPayableResource::make($accountPayable)->response();
    }

    public function destroy(AccountPayable $accountPayable): JsonResponse
    {
        $this->authorizeTenantResource($accountPayable);
        $accountPayable->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(AccountPayable $record): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($record->tenant_id !== $tenantId, 404);
    }
}

