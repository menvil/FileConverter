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
use App\Support\Converters\Exceptions\UnsupportedFormatException;
use App\Support\UI\UiDomainErrorMapper;

it('maps unsupported format to readable message', function () {
    $mapper = app(UiDomainErrorMapper::class);
    $msg = $mapper->map(UnsupportedFormatException::forFormat('heic'));

    expect($msg->message)->toContain('not supported');
});

it('maps unsupported conversion to readable message', function () {
    $mapper = app(UiDomainErrorMapper::class);
    $msg = $mapper->map(UnsupportedConversionException::forPair('png', 'mp3'));

    expect($msg->message)->toContain('not supported');
});

it('maps invalid options to readable message', function () {
    $mapper = app(UiDomainErrorMapper::class);
    $msg = $mapper->map(InvalidConverterOptionsException::withErrors(['quality' => 'Invalid.']));

    expect($msg->message)->toContain('invalid');
});

it('maps file too large to readable message', function () {
    $mapper = app(UiDomainErrorMapper::class);
    $msg = $mapper->map(FileTooLargeException::forLimit(50_000_000, 25_000_000));

    expect($msg->message)->toContain('large');
});

it('maps storage limit exceeded to readable message', function () {
    $mapper = app(UiDomainErrorMapper::class);
    $msg = $mapper->map(StorageLimitExceededException::make(250, 200 * 1024 * 1024, 100 * 1024 * 1024));

    expect($msg->message)->toContain('storage');
});

it('maps insufficient credits to readable message with action label', function () {
    $mapper = app(UiDomainErrorMapper::class);
    $msg = $mapper->map(InsufficientCreditsException::forCost(5, 2));

    expect($msg->message)->toContain('credits');
    expect($msg->actionLabel)->not->toBeNull();
});

it('maps feature not available to readable message', function () {
    $mapper = app(UiDomainErrorMapper::class);
    $msg = $mapper->map(FeatureNotAvailableException::forFeature('api_access', 'free'));

    expect($msg->message)->toContain('plan');
});

it('maps conversion failed to readable message', function () {
    $mapper = app(UiDomainErrorMapper::class);
    $msg = $mapper->map(ConversionFailedException::forJob('conv_1', 'driver error'));

    expect($msg->message)->toContain('failed');
});

it('maps result expired to readable message', function () {
    $mapper = app(UiDomainErrorMapper::class);
    $msg = $mapper->map(ConversionResultExpiredException::forConversion('conv_1'));

    expect($msg->message)->toContain('expired');
});

it('maps unknown exception to generic message without stack trace', function () {
    $mapper = app(UiDomainErrorMapper::class);
    $msg = $mapper->map(new RuntimeException('Internal system detail that should not leak.'));

    expect($msg->message)->not->toContain('Internal system detail');
    expect($msg->message)->toBeString();
});
