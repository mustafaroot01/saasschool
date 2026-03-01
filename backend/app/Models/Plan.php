<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use \App\Traits\LogsActivity;

class Plan extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'max_students',
        'max_teachers',
        'max_admins',
        'storage_limit_mb',
        'notifications_limit',
        'price',
        'duration_months',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
