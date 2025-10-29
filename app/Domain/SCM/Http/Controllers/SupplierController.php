<?php

namespace App\Domain\SCM\Http\Controllers;

use App\Domain\SCM\Http\Requests\SupplierStoreRequest;
use App\Domain\SCM\Http\Requests\SupplierUpdateRequest;
use App\Domain\SCM\Http\Resources\SupplierResource;
use App\Domain\SCM\Models\Supplier;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $suppliers = Supplier::query()
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('search'), function ($query, $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('contact_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return SupplierResource::collection($suppliers)->response();
    }

    public function store(SupplierStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $supplier = Supplier::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return SupplierResource::make($supplier)->response()->setStatusCode(201);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        $this->authorizeTenantResource($supplier);

        return SupplierResource::make($supplier)->response();
    }

    public function update(SupplierUpdateRequest $request, Supplier $supplier): JsonResponse
    {
        $this->authorizeTenantResource($supplier);

        $supplier->update($request->validated());

        return SupplierResource::make($supplier->refresh())->response();
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $this->authorizeTenantResource($supplier);

        $supplier->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Supplier $supplier): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($supplier->tenant_id !== $tenantId, 404);
    }
}

