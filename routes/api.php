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
            if (app()->environment('testing')) {
                Route::get('/auth-test', fn () => response()->json(['authenticated' => true]));
            }
        });

        Route::middleware(['api.key', 'api.access', 'throttle:api-v1'])->group(function () {
            if (app()->environment('testing')) {
                Route::get('/access-test', fn () => response()->json(['access' => true]));
            }
        });
    });
