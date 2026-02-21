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
// These routes MUST come before the catch-all route
Route::prefix('docs')->group(function () {
    Route::get('/', function () {
        return view('docs.index');
    })->name('docs.index');

    Route::get('/authentication', function () {
        return view('docs.authentication');
    })->name('docs.authentication');

    Route::get('/customers', function () {
        return view('docs.customers');
    })->name('docs.customers');

    Route::get('/virtual-accounts', function () {
        return view('docs.virtual-accounts');
    })->name('docs.virtual-accounts');

    Route::get('/transfers', function () {
        return view('docs.transfers');
    })->name('docs.transfers');

    Route::get('/webhooks', function () {
        return view('docs.webhooks');
    })->name('docs.webhooks');

    Route::get('/errors', function () {
        return view('docs.errors');
    })->name('docs.errors');

    Route::get('/sandbox', function () {
        return view('docs.sandbox');
    })->name('docs.sandbox');

    Route::get('/banks', function () {
        return view('docs.banks');
    })->name('docs.banks');
});

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
// Explicitly exclude /docs and /api routes
Route::get('/{any}', function () {
    return file_get_contents(public_path('index.html'));
})->where('any', '^(?!docs|api).*');
