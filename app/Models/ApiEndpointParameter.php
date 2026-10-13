<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiEndpointParameter extends Model
{
    protected $fillable = [
        'api_endpoint_id',
        'name',
        'type',
        'location',
        'is_required',
        'source_type',
        'source_endpoint_id',
        'source_field',
        'source_note',
        'example',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function endpoint()
    {
        return $this->belongsTo(ApiEndpoint::class, 'api_endpoint_id');
    }

    public function sourceEndpoint()
    {
        return $this->belongsTo(ApiEndpoint::class, 'source_endpoint_id');
    }
}
