<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiWorkflow extends Model
{
    protected $table = 'api_workflows';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'title',
        'badge',
        'icon',
        'summary',
        'roles',
        'rules',
        'phases',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'roles' => 'array',
        'rules' => 'array',
        'phases' => 'array',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];
}
