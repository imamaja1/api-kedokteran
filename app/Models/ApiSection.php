<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiSection extends Model
{
    protected $fillable = ['title', 'sort_order'];

    public function endpoints()
    {
        return $this->hasMany(ApiEndpoint::class)->orderBy('sort_order');
    }
}
