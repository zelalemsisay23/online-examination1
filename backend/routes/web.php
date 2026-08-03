<?php

use Illuminate\Support\Facades\Route;

// Backend is a pure REST API consumed by the Vue.js frontend.
// No web views are served from here.
Route::get('/', function () {
    return response()->json([
        'app'     => 'Online Examination System — API',
        'version' => '1.0.0',
        'docs'    => '/api',
    ]);
});
