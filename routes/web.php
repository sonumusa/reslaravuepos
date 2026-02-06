<?php

use Illuminate\Support\Facades\Route;

// Catch-all route for SPA, excluding API routes
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api).*$');
