<?php

declare(strict_types=1);

use App\Exceptions\DomainExceptionContract;
use App\Exceptions\Files\FileTooLargeException;
use App\Exceptions\Storage\StorageLimitExceededException;
use App\Support\Converters\Exceptions\InvalidConverterOptionsException;

it('creates invalid options exception with field errors', function () {
    $exception = InvalidConverterOptionsException::withErrors([
        'quality' => 'Invalid quality value.',
    ]);

    expect($exception->code())->toBe('invalid_options');
    expect($exception->details())->toHaveKey('errors');
    expect($exception->details()['errors'])->toMatchArray(['quality' => 'Invalid quality value.']);
    expect($exception)->toBeInstanceOf(DomainExceptionContract::class);
});

it('creates file too large exception with limits', function () {
    $exception = FileTooLargeException::forLimit(
        actualBytes: 50_000_000,
        maxBytes: 25_000_000,
    );

    expect($exception->code())->toBe('file_too_large');
    expect($exception->details())->toMatchArray([
        'actual_bytes' => 50_000_000,
        'max_bytes' => 25_000_000,
    ]);
    expect($exception)->toBeInstanceOf(DomainExceptionContract::class);
});

it('creates storage limit exceeded exception with used and max bytes', function () {
    $exception = StorageLimitExceededException::make(250, 200 * 1024 * 1024, 100 * 1024 * 1024);

    expect($exception->code())->toBe('storage_limit_exceeded');
    expect($exception->details())->toHaveKey('used_bytes');
    expect($exception->details())->toHaveKey('max_bytes');
    expect($exception)->toBeInstanceOf(DomainExceptionContract::class);
});
