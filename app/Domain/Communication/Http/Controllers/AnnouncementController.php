<?php

namespace App\Domain\Communication\Http\Controllers;

use App\Domain\Communication\Http\Requests\AnnouncementStoreRequest;
use App\Domain\Communication\Http\Requests\AnnouncementUpdateRequest;
use App\Domain\Communication\Http\Resources\AnnouncementResource;
use App\Domain\Communication\Models\Announcement;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $announcements = Announcement::query()
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('priority'), fn ($query, $priority) => $query->where('priority', $priority))
            ->when($request->query('search'), function ($query, $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('body', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('publish_at')
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return AnnouncementResource::collection($announcements)->response();
    }

    public function store(AnnouncementStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $announcement = Announcement::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return AnnouncementResource::make($announcement)->response()->setStatusCode(201);
    }

    public function show(Announcement $announcement): JsonResponse
    {
        $this->authorizeTenantResource($announcement);

        return AnnouncementResource::make($announcement)->response();
    }

    public function update(AnnouncementUpdateRequest $request, Announcement $announcement): JsonResponse
    {
        $this->authorizeTenantResource($announcement);

        $announcement->update($request->validated());

        return AnnouncementResource::make($announcement)->response();
    }

    public function destroy(Announcement $announcement): JsonResponse
    {
        $this->authorizeTenantResource($announcement);
        $announcement->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Announcement $announcement): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($announcement->tenant_id !== $tenantId, 404);
    }
}
