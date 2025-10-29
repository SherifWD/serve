<?php

namespace App\Domain\CMMS\Http\Controllers;

use App\Domain\CMMS\Http\Requests\SparePartStoreRequest;
use App\Domain\CMMS\Http\Requests\SparePartUpdateRequest;
use App\Domain\CMMS\Http\Resources\SparePartResource;
use App\Domain\CMMS\Models\SparePart;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SparePartController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $parts = SparePart::query()
            ->where('tenant_id', $tenantId)
            ->when($request->query('search'), function ($query, $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return SparePartResource::collection($parts)->response();
    }

    public function store(SparePartStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $part = SparePart::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return SparePartResource::make($part)->response()->setStatusCode(201);
    }

    public function show(SparePart $sparePart): JsonResponse
    {
        $this->authorizeTenantResource($sparePart);

        return SparePartResource::make($sparePart)->response();
    }

    public function update(SparePartUpdateRequest $request, SparePart $sparePart): JsonResponse
    {
        $this->authorizeTenantResource($sparePart);

        $sparePart->update($request->validated());

        return SparePartResource::make($sparePart)->response();
    }

    public function destroy(SparePart $sparePart): JsonResponse
    {
        $this->authorizeTenantResource($sparePart);
        $sparePart->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(SparePart $sparePart): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($sparePart->tenant_id !== $tenantId, 404);
    }
}

