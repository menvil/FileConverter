<?php

declare(strict_types=1);

use App\Exceptions\Billing\InsufficientCreditsException;
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

it('returns null for unknown exceptions', function () {
    $mapper = app(ApiExceptionMapper::class);
    $mapped = $mapper->map(new RuntimeException('Some other error'));

    expect($mapped)->toBeNull();
});
