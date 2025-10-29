<?php

namespace App\Domain\Finance\Http\Controllers;

use App\Domain\Finance\Http\Requests\AccountReceivableStoreRequest;
use App\Domain\Finance\Http\Requests\AccountReceivableUpdateRequest;
use App\Domain\Finance\Http\Resources\AccountReceivableResource;
use App\Domain\Finance\Models\AccountReceivable;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountReceivableController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $records = AccountReceivable::query()
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('invoice_date')
            ->paginate($request->integer('per_page', 20));

        return AccountReceivableResource::collection($records)->response();
    }

    public function store(AccountReceivableStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $record = AccountReceivable::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return AccountReceivableResource::make($record)->response()->setStatusCode(201);
    }

    public function show(AccountReceivable $accountReceivable): JsonResponse
    {
        $this->authorizeTenantResource($accountReceivable);

        return AccountReceivableResource::make($accountReceivable)->response();
    }

    public function update(AccountReceivableUpdateRequest $request, AccountReceivable $accountReceivable): JsonResponse
    {
        $this->authorizeTenantResource($accountReceivable);

        $accountReceivable->update($request->validated());

        return AccountReceivableResource::make($accountReceivable)->response();
    }

    public function destroy(AccountReceivable $accountReceivable): JsonResponse
    {
        $this->authorizeTenantResource($accountReceivable);
        $accountReceivable->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(AccountReceivable $record): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($record->tenant_id !== $tenantId, 404);
    }
}

