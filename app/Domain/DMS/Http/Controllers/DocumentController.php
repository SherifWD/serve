<?php

namespace App\Domain\DMS\Http\Controllers;

use App\Domain\DMS\Http\Requests\DocumentStoreRequest;
use App\Domain\DMS\Http\Requests\DocumentUpdateRequest;
use App\Domain\DMS\Http\Resources\DocumentResource;
use App\Domain\DMS\Models\Document;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $documents = Document::query()
            ->where('tenant_id', $tenantId)
            ->with($request->boolean('with_folder') ? ['folder'] : [])
            ->when($request->query('folder_id'), fn ($query, $folderId) => $query->where('folder_id', $folderId))
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('search'), function ($query, $term) {
                $query->where(function ($sub) use ($term) {
                    $sub->where('title', 'like', "%{$term}%")
                        ->orWhere('reference', 'like', "%{$term}%");
                });
            })
            ->orderByDesc('updated_at')
            ->paginate($request->integer('per_page', 20));

        return DocumentResource::collection($documents)->response();
    }

    public function store(DocumentStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $document = Document::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return DocumentResource::make($document->load('folder'))->response()->setStatusCode(201);
    }

    public function show(Document $document): JsonResponse
    {
        $this->authorizeTenantResource($document);

        $document->load(['folder', 'versions.uploader']);

        return DocumentResource::make($document)->response();
    }

    public function update(DocumentUpdateRequest $request, Document $document): JsonResponse
    {
        $this->authorizeTenantResource($document);

        $document->update($request->validated());

        return DocumentResource::make($document->load('folder'))->response();
    }

    public function destroy(Document $document): JsonResponse
    {
        $this->authorizeTenantResource($document);
        $document->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Document $document): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($document->tenant_id !== $tenantId, 404);
    }
}
