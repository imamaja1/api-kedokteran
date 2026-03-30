<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ApiConnection extends Model
{
    protected $fillable = [
        'name',
        'description',
        'base_url',
        'username',
        'password',
        'cookie',
        'cookie_expires_at',
        'extra_headers',
        'is_active',
    ];

    protected $casts = [
        'extra_headers'     => 'array',
        'is_active'         => 'boolean',
        'cookie_expires_at' => 'datetime',
    ];

    /**
     * Enkripsi password sebelum disimpan ke database.
     */
    public function setPasswordAttribute(?string $value): void
    {
        $this->attributes['password'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Dekripsi password saat diambil dari database.
     */
    public function getPasswordAttribute(?string $value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Cek apakah cookie session masih berlaku.
     */
    public function isCookieValid(): bool
    {
        if ($this->cookie_expires_at === null) {
            return true;
        }

        return $this->cookie_expires_at->isFuture();
    }
}
