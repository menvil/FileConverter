<?php

declare(strict_types=1);

use App\Exceptions\DomainExceptionContract;
use App\Support\Conversions\Exceptions\UnsupportedConversionException;
use App\Support\Converters\Exceptions\UnsupportedFormatException;

it('creates unsupported format exception with stable code and details', function () {
    $exception = UnsupportedFormatException::forFormat('heic');

    expect($exception->code())->toBe('unsupported_format');
    expect($exception->details())->toMatchArray(['format' => 'heic']);
    expect($exception)->toBeInstanceOf(DomainExceptionContract::class);
});

it('creates unsupported conversion exception with stable code and details', function () {
    $exception = UnsupportedConversionException::forPair('png', 'mp3');

    expect($exception->code())->toBe('unsupported_conversion');
    expect($exception->details())->toMatchArray([
        'source_format' => 'png',
        'target_format' => 'mp3',
    ]);
    expect($exception)->toBeInstanceOf(DomainExceptionContract::class);
});
