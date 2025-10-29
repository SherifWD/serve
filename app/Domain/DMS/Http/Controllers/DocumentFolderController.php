<?php

namespace App\Domain\DMS\Http\Controllers;

use App\Domain\DMS\Http\Requests\DocumentFolderStoreRequest;
use App\Domain\DMS\Http\Requests\DocumentFolderUpdateRequest;
use App\Domain\DMS\Http\Resources\DocumentFolderResource;
use App\Domain\DMS\Models\DocumentFolder;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentFolderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $folders = DocumentFolder::query()
            ->where('tenant_id', $tenantId)
            ->when($request->boolean('with_children'), fn ($query) => $query->with('children'))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return DocumentFolderResource::collection($folders)->response();
    }

    public function store(DocumentFolderStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $folder = DocumentFolder::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return DocumentFolderResource::make($folder)->response()->setStatusCode(201);
    }

    public function show(DocumentFolder $documentFolder): JsonResponse
    {
        $this->authorizeTenantResource($documentFolder);

        $documentFolder->load(['children', 'documents']);

        return DocumentFolderResource::make($documentFolder)->response();
    }

    public function update(DocumentFolderUpdateRequest $request, DocumentFolder $documentFolder): JsonResponse
    {
        $this->authorizeTenantResource($documentFolder);

        $documentFolder->update($request->validated());

        return DocumentFolderResource::make($documentFolder)->response();
    }

    public function destroy(DocumentFolder $documentFolder): JsonResponse
    {
        $this->authorizeTenantResource($documentFolder);
        $documentFolder->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(DocumentFolder $folder): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($folder->tenant_id !== $tenantId, 404);
    }
}
