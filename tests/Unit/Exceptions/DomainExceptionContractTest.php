<?php

declare(strict_types=1);

use App\Exceptions\DomainException;
use App\Exceptions\DomainExceptionContract;

it('exposes domain exception code and details', function () {
    $exception = new class('Test message.') extends DomainException implements DomainExceptionContract
    {
        public function code(): string
        {
            return 'test_error';
        }

        public function details(): array
        {
            return ['foo' => 'bar'];
        }
    };

    expect($exception->code())->toBe('test_error');
    expect($exception->details())->toBe(['foo' => 'bar']);
    expect($exception->getMessage())->toBe('Test message.');
});

it('abstract domain exception base class provides code and details', function () {
    $exception = new class('Base message.') extends DomainException
    {
        public function code(): string
        {
            return 'base_error';
        }
    };

    expect($exception->code())->toBe('base_error');
    expect($exception->details())->toBe([]);
    expect($exception->getMessage())->toBe('Base message.');
});
