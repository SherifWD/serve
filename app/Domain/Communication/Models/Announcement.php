<?php

namespace App\Domain\Communication\Models;

use App\Domain\Shared\Models\TenantModel;

class Announcement extends TenantModel
{
    protected $table = 'communication_announcements';

    protected $fillable = [
        'tenant_id',
        'title',
        'body',
        'priority',
        'status',
        'publish_at',
        'expires_at',
        'audiences',
        'attachments',
        'metadata',
    ];

    protected $casts = [
        'publish_at' => 'datetime',
        'expires_at' => 'datetime',
        'audiences' => 'array',
        'attachments' => 'array',
        'metadata' => 'array',
    ];
}
