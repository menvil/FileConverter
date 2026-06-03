<?php

declare(strict_types=1);

use App\Exceptions\Billing\InsufficientCreditsException;
use App\Exceptions\Storage\StorageLimitExceededException;
use App\Support\Conversions\Exceptions\UnsupportedConversionException;
use App\Support\Converters\Exceptions\InvalidConverterOptionsException;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/api/v1/test-insufficient-credits', fn () => throw InsufficientCreditsException::make(2, 1));
    Route::get('/api/v1/test-unsupported-conversion', fn () => throw UnsupportedConversionException::forPair('png', 'mp3'));
    Route::get('/api/v1/test-invalid-options', fn () => throw InvalidConverterOptionsException::becauseOptionIsRequired('quality'));
    Route::get('/api/v1/test-storage-limit', fn () => throw StorageLimitExceededException::make(250, 200 * 1024 * 1024, 100 * 1024 * 1024));
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
