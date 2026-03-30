<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiEndpoint extends Model
{
    protected $fillable = [
        'api_section_id', 'title', 'description',
        'method', 'url', 'headers', 'body', 'response_example', 'sort_order',
    ];

    public function section()
    {
        return $this->belongsTo(ApiSection::class, 'api_section_id');
    }
}
