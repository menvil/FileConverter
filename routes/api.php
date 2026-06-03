<?php

use App\Http\Controllers\Api\V1\ConversionController;
use App\Http\Controllers\Api\V1\ConverterController;
use App\Http\Controllers\Api\V1\CreditController;
use App\Http\Controllers\Api\V1\FileController;
use Illuminate\Routing\Middleware\SubstituteBindings;
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

        Route::middleware(['throttle:api-v1', 'api.key', 'api.access'])->group(function () {
            if (app()->environment('testing')) {
                Route::get('/access-test', fn () => response()->json(['access' => true]));
            }

            Route::get('/converters', [ConverterController::class, 'index'])->name('converters.index');
            Route::get('/converters/{source}/{target}/schema', [ConverterController::class, 'schema'])->name('converters.schema');

            Route::post('/files', [FileController::class, 'store'])->name('files.store');
            Route::get('/files/{fileId}/targets', [FileController::class, 'targets'])
                ->name('files.targets')
                ->withoutMiddleware(SubstituteBindings::class);

            Route::post('/conversions/estimate', [ConversionController::class, 'estimate'])->name('conversions.estimate');
            Route::post('/conversions', [ConversionController::class, 'store'])->name('conversions.store');
            Route::get('/conversions/{conversionId}', [ConversionController::class, 'show'])
                ->name('conversions.show')
                ->withoutMiddleware(SubstituteBindings::class);
            Route::get('/conversions/{conversionId}/download', [ConversionController::class, 'download'])
                ->name('conversions.download')
                ->withoutMiddleware(SubstituteBindings::class);

            Route::get('/credits/balance', [CreditController::class, 'balance'])->name('credits.balance');
        });
    });
