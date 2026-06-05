<?php

declare(strict_types=1);

use App\Exceptions\Billing\InsufficientCreditsException;
use App\Exceptions\Conversions\ConversionFailedException;
use App\Exceptions\Conversions\ConversionResultExpiredException;
use App\Exceptions\Features\FeatureNotAvailableException;
use App\Exceptions\Files\FileTooLargeException;
use App\Exceptions\Files\UnsupportedFileFormatException;
use App\Exceptions\Storage\StorageLimitExceededException;
use App\Support\Api\ApiExceptionMapper;
use App\Support\Conversions\Exceptions\UnsupportedConversionException;
use App\Support\Converters\Exceptions\InvalidConverterOptionsException;
use App\Support\Converters\Exceptions\UnsupportedFormatException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

it('maps insufficient credits exception to api error', function () {
    $mapper = app(ApiExceptionMapper::class);
    $mapped = $mapper->map(InsufficientCreditsException::make(required: 2, available: 1));

    expect($mapped->code)->toBe('insufficient_credits');
    expect($mapped->status)->toBe(402);
});

it('maps unsupported format exception to api error', function () {
    $mapper = app(ApiExceptionMapper::class);
    $mapped = $mapper->map(UnsupportedFormatException::forInput('xyz'));

    expect($mapped->code)->toBe('unsupported_format');
    expect($mapped->status)->toBe(422);
});

it('maps unsupported conversion exception to api error', function () {
    $mapper = app(ApiExceptionMapper::class);
    $mapped = $mapper->map(UnsupportedConversionException::forPair('png', 'mp3'));

    expect($mapped->code)->toBe('unsupported_conversion');
    expect($mapped->status)->toBe(422);
});

it('maps invalid converter options exception to api error', function () {
    $mapper = app(ApiExceptionMapper::class);
    $mapped = $mapper->map(InvalidConverterOptionsException::becauseOptionIsRequired('quality'));

    expect($mapped->code)->toBe('invalid_options');
    expect($mapped->status)->toBe(422);
});

it('maps unsupported file format exception to api error', function () {
    $mapper = app(ApiExceptionMapper::class);
    $mapped = $mapper->map(UnsupportedFileFormatException::forFile('file.xyz'));

    expect($mapped->code)->toBe('unsupported_format');
    expect($mapped->status)->toBe(422);
});

it('maps storage limit exceeded exception to api error', function () {
    $mapper = app(ApiExceptionMapper::class);
    $mapped = $mapper->map(StorageLimitExceededException::make(250, 200 * 1024 * 1024, 100 * 1024 * 1024));

    expect($mapped->code)->toBe('storage_limit_exceeded');
    expect($mapped->status)->toBe(413);
});

it('maps authentication exception to api error', function () {
    $mapper = app(ApiExceptionMapper::class);
    $mapped = $mapper->map(new AuthenticationException);

    expect($mapped->code)->toBe('unauthorized');
    expect($mapped->status)->toBe(401);
});

it('maps authorization exception to api error', function () {
    $mapper = app(ApiExceptionMapper::class);
    $mapped = $mapper->map(new AuthorizationException);

    expect($mapped->code)->toBe('forbidden');
    expect($mapped->status)->toBe(403);
});

it('maps throttle exception to api error', function () {
    $mapper = app(ApiExceptionMapper::class);
    $mapped = $mapper->map(new ThrottleRequestsException('Too Many Attempts.'));

    expect($mapped->code)->toBe('rate_limited');
    expect($mapped->status)->toBe(429);
});

it('maps file too large exception to api error', function () {
    $mapper = app(ApiExceptionMapper::class);
    $mapped = $mapper->map(FileTooLargeException::forLimit(50_000_000, 25_000_000));

    expect($mapped->code)->toBe('file_too_large');
    expect($mapped->status)->toBe(413);
    expect($mapped->details)->toHaveKey('actual_bytes');
});

it('maps feature not available exception to api error', function () {
    $mapper = app(ApiExceptionMapper::class);
    $mapped = $mapper->map(FeatureNotAvailableException::forFeature('api_access', 'free'));

    expect($mapped->code)->toBe('feature_not_available');
    expect($mapped->status)->toBe(403);
});

it('maps conversion failed exception to api error', function () {
    $mapper = app(ApiExceptionMapper::class);
    $mapped = $mapper->map(ConversionFailedException::forJob('conv_1', 'driver error'));

    expect($mapped->code)->toBe('conversion_failed');
    expect($mapped->status)->toBe(500);
});

it('maps conversion result expired exception to api error', function () {
    $mapper = app(ApiExceptionMapper::class);
    $mapped = $mapper->map(ConversionResultExpiredException::forConversion('conv_1'));

    expect($mapped->code)->toBe('result_expired');
    expect($mapped->status)->toBe(410);
});

it('returns null for unknown exceptions', function () {
    $mapper = app(ApiExceptionMapper::class);
    $mapped = $mapper->map(new RuntimeException('Some other error'));

    expect($mapped)->toBeNull();
});
