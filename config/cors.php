<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS)
    |--------------------------------------------------------------------------
    | Konfigurasi untuk Sanctum Cookie SPA — supports_credentials HARUS true.
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_unique(array_merge(
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://localhost:8000,http://127.0.0.1:5173')),
        array_filter([
            env('APP_URL'),
            'https://api-kedokteran.ubg.ac.id',
            'https://api-kedokteran-test.ubg.ac.id',
            'https://siska.ubg.ac.id',
        ])
    )))),

    'allowed_origins_patterns' => ['*.ubg.ac.id'],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    /*
     * WAJIB true untuk Sanctum Cookie agar browser mengirim cookie
     */
    'supports_credentials' => true,

];
