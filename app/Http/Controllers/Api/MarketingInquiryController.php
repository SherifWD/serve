<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketingInquiry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MarketingInquiryController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['new', 'reviewing', 'contacted', 'qualified', 'not_fit', 'closed'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = MarketingInquiry::query()
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('full_name', 'like', "%{$search}%")
                        ->orWhere('business_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('pain_points', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->latest();

        $paginator = $query->paginate((int) ($filters['per_page'] ?? 20));

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (MarketingInquiry $inquiry) => $this->serialize($inquiry))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'new_count' => MarketingInquiry::query()->where('status', 'new')->count(),
            ],
        ]);
    }

    public function show(MarketingInquiry $marketingInquiry)
    {
        return response()->json($this->serialize($marketingInquiry));
    }

    public function update(Request $request, MarketingInquiry $marketingInquiry)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'reviewing', 'contacted', 'qualified', 'not_fit', 'closed'])],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $marketingInquiry->update($data);

        return response()->json($this->serialize($marketingInquiry->fresh()));
    }

    private function serialize(MarketingInquiry $inquiry): array
    {
        return [
            'id' => $inquiry->id,
            'full_name' => $inquiry->full_name,
            'business_name' => $inquiry->business_name,
            'role' => $inquiry->role,
            'email' => $inquiry->email,
            'phone' => $inquiry->phone,
            'city' => $inquiry->city,
            'website' => $inquiry->website,
            'business_type' => $inquiry->business_type,
            'branch_count' => $inquiry->branch_count,
            'staff_count' => $inquiry->staff_count,
            'current_system' => $inquiry->current_system,
            'order_channels' => $inquiry->order_channels ?? [],
            'interest_areas' => $inquiry->interest_areas ?? [],
            'devices' => $inquiry->devices ?? [],
            'timeline' => $inquiry->timeline,
            'budget_range' => $inquiry->budget_range,
            'pain_points' => $inquiry->pain_points,
            'success_notes' => $inquiry->success_notes,
            'preferred_contact_method' => $inquiry->preferred_contact_method,
            'best_contact_time' => $inquiry->best_contact_time,
            'consent_to_contact' => (bool) $inquiry->consent_to_contact,
            'status' => $inquiry->status,
            'admin_notes' => $inquiry->admin_notes,
            'source_url' => $inquiry->source_url,
            'created_at' => $inquiry->created_at?->toISOString(),
            'updated_at' => $inquiry->updated_at?->toISOString(),
        ];
    }
}
