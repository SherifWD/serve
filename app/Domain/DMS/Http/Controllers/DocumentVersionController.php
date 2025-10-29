<?php

namespace App\Domain\DMS\Http\Controllers;

use App\Domain\DMS\Http\Requests\DocumentVersionStoreRequest;
use App\Domain\DMS\Http\Requests\DocumentVersionUpdateRequest;
use App\Domain\DMS\Http\Resources\DocumentVersionResource;
use App\Domain\DMS\Models\Document;
use App\Domain\DMS\Models\DocumentVersion;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentVersionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $versions = DocumentVersion::query()
            ->with(['document'])
            ->where('tenant_id', $tenantId)
            ->when($request->query('document_id'), fn ($query, $documentId) => $query->where('document_id', $documentId))
            ->orderByDesc('version_number')
            ->paginate($request->integer('per_page', 20));

        return DocumentVersionResource::collection($versions)->response();
    }

    public function store(DocumentVersionStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $data = $request->validated();

        $document = Document::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $data['document_id'])
            ->firstOrFail();

        $version = DB::transaction(function () use ($document, $tenantId, $data) {
            $nextNumber = $data['version_number']
                ?? ($document->versions()->max('version_number') + 1);

            $version = DocumentVersion::create([
                ...$data,
                'tenant_id' => $tenantId,
                'version_number' => $nextNumber,
            ]);

            $document->forceFill(['latest_version_number' => max($document->latest_version_number, $nextNumber)])->save();

            return $version->load('uploader');
        });

        return DocumentVersionResource::make($version)->response()->setStatusCode(201);
    }

    public function show(DocumentVersion $documentVersion): JsonResponse
    {
        $this->authorizeTenantResource($documentVersion);

        return DocumentVersionResource::make($documentVersion->load(['document', 'uploader']))->response();
    }

    public function update(DocumentVersionUpdateRequest $request, DocumentVersion $documentVersion): JsonResponse
    {
        $this->authorizeTenantResource($documentVersion);
        $data = $request->validated();

        $documentVersion = DB::transaction(function () use ($documentVersion, $data) {
            $documentVersion->update($data);

            $document = $documentVersion->document()->first();
            if ($document) {
                $latest = $document->versions()->max('version_number');
                $document->forceFill(['latest_version_number' => $latest])->save();
            }

            return $documentVersion->load(['document', 'uploader']);
        });

        return DocumentVersionResource::make($documentVersion)->response();
    }

    public function destroy(DocumentVersion $documentVersion): JsonResponse
    {
        $this->authorizeTenantResource($documentVersion);

        DB::transaction(function () use ($documentVersion) {
            $document = $documentVersion->document;
            $documentVersion->delete();

            if ($document) {
                $latest = $document->versions()->max('version_number') ?? 0;
                $document->forceFill(['latest_version_number' => $latest])->save();
            }
        });

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(DocumentVersion $version): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($version->tenant_id !== $tenantId, 404);
    }
}
