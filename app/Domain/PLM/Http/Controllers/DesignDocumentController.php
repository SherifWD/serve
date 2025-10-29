<?php

namespace App\Domain\PLM\Http\Controllers;

use App\Domain\PLM\Http\Requests\DesignDocumentStoreRequest;
use App\Domain\PLM\Http\Requests\DesignDocumentUpdateRequest;
use App\Domain\PLM\Http\Resources\DesignDocumentResource;
use App\Domain\PLM\Models\DesignDocument;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DesignDocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $documents = DesignDocument::query()
            ->where('tenant_id', $tenantId)
            ->when($request->query('product_design_id'), fn ($query, $designId) => $query->where('product_design_id', $designId))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return DesignDocumentResource::collection($documents)->response();
    }

    public function store(DesignDocumentStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $document = DesignDocument::create(array_merge(
            $request->validated(),
            ['tenant_id' => $tenantId]
        ));

        return DesignDocumentResource::make($document)->response()->setStatusCode(201);
    }

    public function show(DesignDocument $designDocument): JsonResponse
    {
        $this->authorizeTenantResource($designDocument);

        return DesignDocumentResource::make($designDocument)->response();
    }

    public function update(DesignDocumentUpdateRequest $request, DesignDocument $designDocument): JsonResponse
    {
        $this->authorizeTenantResource($designDocument);

        $designDocument->update($request->validated());

        return DesignDocumentResource::make($designDocument)->response();
    }

    public function destroy(DesignDocument $designDocument): JsonResponse
    {
        $this->authorizeTenantResource($designDocument);

        $designDocument->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(DesignDocument $designDocument): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        abort_if($designDocument->tenant_id !== $tenantId, 404);
    }
}

