<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function () {
        Route::get('/health', fn () => response()->json([
            'status' => 'ok',
            'version' => 'v1',
        ]))->name('health');

        Route::middleware('api.key')->group(function () {
            // Test-only route to verify auth middleware wiring.
            // Only registered in testing environment.
            if (app()->environment('testing')) {
                Route::get('/auth-test', fn () => response()->json(['authenticated' => true]));
            }
        });
    });
