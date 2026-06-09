<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketingInquiry extends Model
{
    protected $fillable = [
        'full_name',
        'business_name',
        'role',
        'email',
        'phone',
        'city',
        'website',
        'business_type',
        'branch_count',
        'staff_count',
        'current_system',
        'order_channels',
        'interest_areas',
        'devices',
        'timeline',
        'budget_range',
        'pain_points',
        'success_notes',
        'preferred_contact_method',
        'best_contact_time',
        'consent_to_contact',
        'status',
        'admin_notes',
        'source_url',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'branch_count' => 'integer',
        'staff_count' => 'integer',
        'order_channels' => 'array',
        'interest_areas' => 'array',
        'devices' => 'array',
        'consent_to_contact' => 'boolean',
    ];
}
