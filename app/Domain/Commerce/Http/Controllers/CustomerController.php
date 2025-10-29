<?php

namespace App\Domain\Commerce\Http\Controllers;

use App\Domain\Commerce\Http\Requests\CustomerStoreRequest;
use App\Domain\Commerce\Http\Requests\CustomerUpdateRequest;
use App\Domain\Commerce\Http\Resources\CustomerResource;
use App\Domain\Commerce\Models\Customer;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $customers = Customer::query()
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('search'), function ($query, $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return CustomerResource::collection($customers)->response();
    }

    public function store(CustomerStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $customer = Customer::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return CustomerResource::make($customer)->response()->setStatusCode(201);
    }

    public function show(Customer $customer): JsonResponse
    {
        $this->authorizeTenantResource($customer);

        return CustomerResource::make($customer)->response();
    }

    public function update(CustomerUpdateRequest $request, Customer $customer): JsonResponse
    {
        $this->authorizeTenantResource($customer);

        $customer->update($request->validated());

        return CustomerResource::make($customer)->response();
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $this->authorizeTenantResource($customer);
        $customer->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Customer $customer): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($customer->tenant_id !== $tenantId, 404);
    }
}
