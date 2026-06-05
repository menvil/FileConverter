<?php

declare(strict_types=1);

use App\Exceptions\Billing\InsufficientCreditsException;
use App\Exceptions\Conversions\ConversionFailedException;
use App\Exceptions\Conversions\ConversionResultExpiredException;
use App\Exceptions\Features\FeatureNotAvailableException;
use App\Exceptions\Files\FileTooLargeException;
use App\Exceptions\Storage\StorageLimitExceededException;
use App\Support\Conversions\Exceptions\UnsupportedConversionException;
use App\Support\Converters\Exceptions\InvalidConverterOptionsException;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/api/v1/test-insufficient-credits', fn () => throw InsufficientCreditsException::make(2, 1));
    Route::get('/api/v1/test-unsupported-conversion', fn () => throw UnsupportedConversionException::forPair('png', 'mp3'));
    Route::get('/api/v1/test-invalid-options', fn () => throw InvalidConverterOptionsException::becauseOptionIsRequired('quality'));
    Route::get('/api/v1/test-storage-limit', fn () => throw StorageLimitExceededException::make(250, 200 * 1024 * 1024, 100 * 1024 * 1024));
    Route::get('/api/v1/test-file-too-large', fn () => throw FileTooLargeException::forLimit(50_000_000, 25_000_000));
    Route::get('/api/v1/test-feature-not-available', fn () => throw FeatureNotAvailableException::forFeature('api_access', 'free'));
    Route::get('/api/v1/test-conversion-failed', fn () => throw ConversionFailedException::forJob('conv_1', 'driver error'));
    Route::get('/api/v1/test-result-expired', fn () => throw ConversionResultExpiredException::forConversion('conv_1'));
});

it('maps insufficient credits exception to stable api error', function () {
    $this->getJson('/api/v1/test-insufficient-credits')
        ->assertStatus(402)
        ->assertJsonPath('error.code', 'insufficient_credits')
        ->assertJsonStructure(['error' => ['code', 'message', 'details']]);
});

it('maps unsupported conversion exception to stable api error', function () {
    $this->getJson('/api/v1/test-unsupported-conversion')
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'unsupported_conversion');
});

it('maps invalid options exception to stable api error', function () {
    $this->getJson('/api/v1/test-invalid-options')
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'invalid_options');
});

it('maps storage limit exceeded exception to stable api error', function () {
    $this->getJson('/api/v1/test-storage-limit')
        ->assertStatus(413)
        ->assertJsonPath('error.code', 'storage_limit_exceeded');
});

it('maps file too large exception to stable api error', function () {
    $this->getJson('/api/v1/test-file-too-large')
        ->assertStatus(413)
        ->assertJsonPath('error.code', 'file_too_large')
        ->assertJsonStructure(['error' => ['code', 'message', 'details']]);
});

it('maps feature not available exception to stable api error', function () {
    $this->getJson('/api/v1/test-feature-not-available')
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'feature_not_available');
});

it('maps conversion failed exception to stable api error', function () {
    $this->getJson('/api/v1/test-conversion-failed')
        ->assertStatus(500)
        ->assertJsonPath('error.code', 'conversion_failed');
});

it('maps result expired exception to stable api error', function () {
    $this->getJson('/api/v1/test-result-expired')
        ->assertStatus(410)
        ->assertJsonPath('error.code', 'result_expired');
});
