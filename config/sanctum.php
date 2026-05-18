<?php

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Laravel\Sanctum\Sanctum;

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    | Domain yang diizinkan menggunakan cookie-based authentication (SPA).
    |
    | KONFIGURASI PRODUCTION:
    | Tambahkan domain production di .env:
    |   SANCTUM_STATEFUL_DOMAINS=siska.ubg.ac.id,api-kedokteran.ubg.ac.id
    |
    | Wajib juga set di .env:
    |   SESSION_DOMAIN=.ubg.ac.id       ← prefix titik agar berlaku untuk semua subdomain
    |   APP_URL=https://api-kedokteran.ubg.ac.id
    |   SESSION_SECURE_COOKIE=true      ← wajib HTTPS
    */
    'stateful' => explode(',', env(
        'SANCTUM_STATEFUL_DOMAINS',
        'localhost,localhost:3000,localhost:8000,localhost:8080,127.0.0.1,127.0.0.1:3000,127.0.0.1:8000,127.0.0.1:8080,127.0.0.1:5173'
    )),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Guards
    |--------------------------------------------------------------------------
    | Guards yang support Sanctum cookie-based authentication
    */
    'guard' => ['web', 'mahasiswa_web', 'dosen_web', 'staff_web'],

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    | null = tidak kedaluwarsa sampai logout manual
    */
    'expiration' => null,

    /*
    |--------------------------------------------------------------------------
    | Token Prefix
    |--------------------------------------------------------------------------
    */
    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Middleware
    |--------------------------------------------------------------------------
    */
    'middleware' => [
        // authenticate_session sengaja DI-NULL-kan.
        // Sanctum\Http\Middleware\AuthenticateSession membandingkan password_hash
        // antar guard (web vs mahasiswa_web/dosen_web/staff_web). Karena project ini
        // multi-guard dengan model berbeda, mengaktifkannya menyebabkan session
        // admin di-flush saat ada request API dari browser yang sama.
        'authenticate_session' => null,
        'encrypt_cookies' => EncryptCookies::class,
        'validate_csrf_token' => ValidateCsrfToken::class,
    ],

];
