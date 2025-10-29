<?php

namespace App\Domain\CRM\Http\Controllers;

use App\Domain\CRM\Http\Requests\AccountStoreRequest;
use App\Domain\CRM\Http\Requests\AccountUpdateRequest;
use App\Domain\CRM\Http\Resources\AccountResource;
use App\Domain\CRM\Models\Account;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $accounts = Account::query()
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return AccountResource::collection($accounts)->response();
    }

    public function store(AccountStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $account = Account::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return AccountResource::make($account)->response()->setStatusCode(201);
    }

    public function show(Account $account): JsonResponse
    {
        $this->authorizeTenantResource($account);

        return AccountResource::make($account->load(['contacts', 'opportunities', 'serviceCases']))->response();
    }

    public function update(AccountUpdateRequest $request, Account $account): JsonResponse
    {
        $this->authorizeTenantResource($account);

        $account->update($request->validated());

        return AccountResource::make($account)->response();
    }

    public function destroy(Account $account): JsonResponse
    {
        $this->authorizeTenantResource($account);
        $account->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Account $account): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($account->tenant_id !== $tenantId, 404);
    }
}

