<?php

namespace App\Domain\ERP\Http\Controllers;

use App\Domain\ERP\Http\Requests\ItemStoreRequest;
use App\Domain\ERP\Http\Requests\ItemUpdateRequest;
use App\Domain\ERP\Http\Resources\ItemResource;
use App\Domain\ERP\Models\Item;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $items = Item::query()
            ->with('category')
            ->where('tenant_id', $tenantId)
            ->when($request->query('category_id'), fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($request->query('type'), fn ($query, $type) => $query->where('type', $type))
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('search'), fn ($query, $search) => $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return ItemResource::collection($items)->response();
    }

    public function store(ItemStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $item = Item::create(array_merge(
            $request->validated(),
            ['tenant_id' => $tenantId]
        ));

        return ItemResource::make($item->load('category'))->response()->setStatusCode(201);
    }

    public function show(Item $item): JsonResponse
    {
        $this->authorizeTenantResource($item);

        return ItemResource::make($item->load('category'))->response();
    }

    public function update(ItemUpdateRequest $request, Item $item): JsonResponse
    {
        $this->authorizeTenantResource($item);

        $item->update($request->validated());

        return ItemResource::make($item->load('category'))->response();
    }

    public function destroy(Item $item): JsonResponse
    {
        $this->authorizeTenantResource($item);

        $item->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Item $item): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        abort_if($item->tenant_id !== $tenantId, 404);
    }
}

