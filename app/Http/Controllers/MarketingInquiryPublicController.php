<?php

namespace App\Http\Controllers;

use App\Models\MarketingInquiry;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MarketingInquiryPublicController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'business_name' => ['required', 'string', 'max:160'],
            'role' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:160'],
            'phone' => ['required', 'string', 'max:40'],
            'city' => ['nullable', 'string', 'max:120'],
            'website' => ['nullable', 'string', 'max:180'],
            'business_type' => ['required', Rule::in(['cafe', 'restaurant', 'cloud-kitchen', 'bakery', 'multi-concept', 'other'])],
            'branch_count' => ['nullable', 'integer', 'min:1', 'max:500'],
            'staff_count' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'current_system' => ['nullable', 'string', 'max:160'],
            'order_channels' => ['nullable', 'array'],
            'order_channels.*' => ['string', 'max:80'],
            'interest_areas' => ['nullable', 'array'],
            'interest_areas.*' => ['string', 'max:80'],
            'devices' => ['nullable', 'array'],
            'devices.*' => ['string', 'max:80'],
            'timeline' => ['required', Rule::in(['this-month', '1-3-months', '3-6-months', 'researching'])],
            'budget_range' => ['nullable', 'string', 'max:80'],
            'pain_points' => ['required', 'string', 'max:2000'],
            'success_notes' => ['nullable', 'string', 'max:1600'],
            'preferred_contact_method' => ['required', Rule::in(['phone', 'whatsapp', 'email'])],
            'best_contact_time' => ['nullable', 'string', 'max:120'],
            'consent_to_contact' => ['accepted'],
        ]);

        $data['source_url'] = $request->headers->get('referer');
        $data['ip_address'] = $request->ip();
        $data['user_agent'] = substr((string) $request->userAgent(), 0, 500);
        $data['status'] = 'new';

        $inquiry = MarketingInquiry::query()->create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Inquiry received.',
                'data' => ['id' => $inquiry->id],
            ], 201);
        }

        return redirect()
            ->route('marketing.contact')
            ->with('status', 'Thanks. Your survey was received and the Janova team can now review your requirements.');
    }
}
