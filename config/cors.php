<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST'],

    // Atajo temporal: el widget llama directo con fetch() (ver
    // monitor-fiscal-client.js) en vez de pasar por "API Configurations" de
    // Zoho, así que sí queda sujeto a CORS. Zoho sirve los widgets desde
    // subdominios aleatorios de zappsusercontent.com, por eso el patrón
    // comodín en vez de una lista fija.
    'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', 'https://127.0.0.1:5000'))),

    'allowed_origins_patterns' => array_filter(explode(',', env(
        'CORS_ALLOWED_ORIGIN_PATTERNS',
        '#^https://[a-z0-9-]+\.zappsusercontent\.com$#'
    ))),

    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
