<?php

namespace App\Domain\Procurement\Http\Controllers;

use App\Domain\Procurement\Http\Requests\VendorStoreRequest;
use App\Domain\Procurement\Http\Requests\VendorUpdateRequest;
use App\Domain\Procurement\Http\Resources\VendorResource;
use App\Domain\Procurement\Models\Vendor;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $vendors = Vendor::query()
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('search'), function ($query, $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return VendorResource::collection($vendors)->response();
    }

    public function store(VendorStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $vendor = Vendor::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return VendorResource::make($vendor)->response()->setStatusCode(201);
    }

    public function show(Vendor $vendor): JsonResponse
    {
        $this->authorizeTenantResource($vendor);

        return VendorResource::make($vendor)->response();
    }

    public function update(VendorUpdateRequest $request, Vendor $vendor): JsonResponse
    {
        $this->authorizeTenantResource($vendor);

        $vendor->update($request->validated());

        return VendorResource::make($vendor)->response();
    }

    public function destroy(Vendor $vendor): JsonResponse
    {
        $this->authorizeTenantResource($vendor);
        $vendor->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Vendor $vendor): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($vendor->tenant_id !== $tenantId, 404);
    }
}
