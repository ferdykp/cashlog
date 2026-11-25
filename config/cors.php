<?php

$allowedOrigins = ['https://ledger.fdevsite.cloud'];

if (env('APP_ENV', 'production') === 'local') {
    $allowedOrigins = [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        ...$allowedOrigins,
    ];
}

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    /*
    | Origin localhost hanya diizinkan saat APP_ENV=local. Produksi memakai
    | origin HTTPS aplikasi secara eksplisit karena credentials aktif.
    */
    'allowed_origins' => $allowedOrigins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
