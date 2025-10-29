<?php

namespace App\Domain\Shared\Models;

use App\Platform\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

abstract class TenantModel extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'metadata' => 'array',
        'attributes' => 'array',
        'settings' => 'array',
        'address' => 'array',
        'layout' => 'array',
        'specs' => 'array',
        'payload' => 'array',
    ];
}

