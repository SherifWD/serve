<?php

namespace App\Domain\Visitor\Http\Controllers;

use App\Domain\Visitor\Http\Requests\VisitorStoreRequest;
use App\Domain\Visitor\Http\Requests\VisitorUpdateRequest;
use App\Domain\Visitor\Http\Resources\VisitorResource;
use App\Domain\Visitor\Models\Visitor;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $visitors = Visitor::query()
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('search'), function ($query, $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('full_name', 'like', "%{$search}%")
                        ->orWhere('company', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('updated_at')
            ->paginate($request->integer('per_page', 20));

        return VisitorResource::collection($visitors)->response();
    }

    public function store(VisitorStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $visitor = Visitor::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return VisitorResource::make($visitor)->response()->setStatusCode(201);
    }

    public function show(Visitor $visitor): JsonResponse
    {
        $this->authorizeTenantResource($visitor);

        $visitor->load('entries');

        return VisitorResource::make($visitor)->response();
    }

    public function update(VisitorUpdateRequest $request, Visitor $visitor): JsonResponse
    {
        $this->authorizeTenantResource($visitor);

        $visitor->update($request->validated());

        return VisitorResource::make($visitor)->response();
    }

    public function destroy(Visitor $visitor): JsonResponse
    {
        $this->authorizeTenantResource($visitor);
        $visitor->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Visitor $visitor): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($visitor->tenant_id !== $tenantId, 404);
    }
}
