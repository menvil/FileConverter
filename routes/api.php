<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function () {
        Route::get('/health', fn () => response()->json([
            'status' => 'ok',
            'version' => 'v1',
        ]))->name('health');
    });
