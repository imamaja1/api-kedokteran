<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiEndpoint extends Model
{
    protected $fillable = [
        'api_section_id', 'title', 'description',
        'method', 'url', 'headers', 'body', 'response_example', 'sort_order',
    ];

    protected $appends = ['auth_role'];

    public function section()
    {
        return $this->belongsTo(ApiSection::class, 'api_section_id');
    }

    public function parameters()
    {
        return $this->hasMany(ApiEndpointParameter::class, 'api_endpoint_id')->orderBy('sort_order');
    }

    public function dependentParameters()
    {
        return $this->hasMany(ApiEndpointParameter::class, 'source_endpoint_id');
    }

    /**
     * Mendapatkan role guard yang diharapkan berdasarkan URL atau seksi
     */
    public function getAuthRoleAttribute(): string
    {
        $url = strtolower($this->url);
        $sectionTitle = strtolower($this->section?->title ?? '');

        // 1. Cek prefix path URL utama
        if (str_starts_with($url, '/api/staff') || str_starts_with($url, '/staff')) {
            return 'Staff';
        }
        if (str_starts_with($url, '/api/dosen') || str_starts_with($url, '/dosen')) {
            return 'Dosen';
        }
        if (str_starts_with($url, '/api/mhs') || str_starts_with($url, '/mhs') || str_contains($url, '/auth/mhs')) {
            return 'Mahasiswa';
        }

        // 2. Cek judul seksi
        if (str_contains($sectionTitle, 'staff')) {
            return 'Staff';
        }
        if (str_contains($sectionTitle, 'dosen')) {
            return 'Dosen';
        }
        if (str_contains($sectionTitle, 'mahasiswa')) {
            return 'Mahasiswa';
        }

        return 'Public / Session';
    }
}
