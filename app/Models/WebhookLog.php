<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    use HasFactory; protected $guarded =[];
    // No standard relationships, but you could link to a branch, order, or user if desired
// Example (if webhooks are branch-specific):
public function branch() {
    return $this->belongsTo(Branch::class);
}

}
