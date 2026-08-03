<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS)
    |--------------------------------------------------------------------------
    |
    | The allowed_origins list should contain the URL of your Vue.js frontend.
    | In development that is http://localhost:5173 (Vite default).
    | In production replace with your actual domain.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        env('FRONTEND_URL', 'https://online-examination1.vercel.app'),
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Credentials must be true when the frontend sends cookies or Auth headers
    'supports_credentials' => true,

];
