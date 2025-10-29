<?php

namespace App\Domain\Finance\Http\Controllers;

use App\Domain\Finance\Http\Requests\LedgerAccountStoreRequest;
use App\Domain\Finance\Http\Requests\LedgerAccountUpdateRequest;
use App\Domain\Finance\Http\Resources\LedgerAccountResource;
use App\Domain\Finance\Models\LedgerAccount;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LedgerAccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $accounts = LedgerAccount::query()
            ->where('tenant_id', $tenantId)
            ->when($request->query('type'), fn ($q, $type) => $q->where('account_type', $type))
            ->orderBy('code')
            ->paginate($request->integer('per_page', 50));

        return LedgerAccountResource::collection($accounts)->response();
    }

    public function store(LedgerAccountStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $account = LedgerAccount::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return LedgerAccountResource::make($account)->response()->setStatusCode(201);
    }

    public function show(LedgerAccount $ledgerAccount): JsonResponse
    {
        $this->authorizeTenantResource($ledgerAccount);

        return LedgerAccountResource::make($ledgerAccount)->response();
    }

    public function update(LedgerAccountUpdateRequest $request, LedgerAccount $ledgerAccount): JsonResponse
    {
        $this->authorizeTenantResource($ledgerAccount);

        $ledgerAccount->update($request->validated());

        return LedgerAccountResource::make($ledgerAccount)->response();
    }

    public function destroy(LedgerAccount $ledgerAccount): JsonResponse
    {
        $this->authorizeTenantResource($ledgerAccount);
        $ledgerAccount->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(LedgerAccount $ledgerAccount): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($ledgerAccount->tenant_id !== $tenantId, 404);
    }
}

