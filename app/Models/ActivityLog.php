<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'guard',
        'user_id',
        'user_type',
        'method',
        'path',
        'ip_address',
        'user_agent',
        'status_code',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
