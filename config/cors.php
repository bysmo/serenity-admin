<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration CORS restrictive pour Serenity Admin.
    | Seuls les domaines de confiance sont autorisés à accéder à l'API.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    // Restriction CORS : seuls les domaines autorisés peuvent accéder à l'API
    // Remplacez par vos domaines de production
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'https://localhost')),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization', 'Accept', 'X-Signature', 'PayDunya-Signature'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => true,

];
