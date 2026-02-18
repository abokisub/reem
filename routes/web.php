<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

// Public API Documentation (No Authentication Required)
Route::get('/docs', function () {
    return view('docs.index');
})->name('docs.index');

Route::get('/docs/authentication', function () {
    return view('docs.authentication');
})->name('docs.authentication');

Route::get('/docs/customers', function () {
    return view('docs.customers');
})->name('docs.customers');

Route::get('/docs/virtual-accounts', function () {
    return view('docs.virtual-accounts');
})->name('docs.virtual-accounts');

Route::get('/docs/transfers', function () {
    return view('docs.transfers');
})->name('docs.transfers');

Route::get('/docs/webhooks', function () {
    return view('docs.webhooks');
})->name('docs.webhooks');

Route::get('/docs/errors', function () {
    return view('docs.errors');
})->name('docs.errors');

Route::get('/docs/sandbox', function () {
    return view('docs.sandbox');
})->name('docs.sandbox');

Route::get('/docs/banks', function () {
    return view('docs.banks');
})->name('docs.banks');

// Health Check Endpoint
Route::get('/api/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'version' => '1.0.0',
    ]);
});

// Catch-all route for React SPA
// This MUST be the last route to avoid conflicts
// It serves the React app for all routes that don't match above
Route::get('/{any}', function () {
    return view('index');
})->where('any', '.*');
