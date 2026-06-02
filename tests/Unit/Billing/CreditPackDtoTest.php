<?php

declare(strict_types=1);

use App\Data\Billing\CreditPackDto;

it('creates credit pack dto from config array', function () {
    $dto = CreditPackDto::fromConfig('small', [
        'label' => '500 Credits',
        'credits' => 500,
        'stripe_price_id' => 'price_small',
        'description' => 'Good for occasional conversions.',
    ]);

    expect($dto->key)->toBe('small');
    expect($dto->label)->toBe('500 Credits');
    expect($dto->credits)->toBe(500);
    expect($dto->stripePriceId)->toBe('price_small');
    expect($dto->description)->toBe('Good for occasional conversions.');
});

it('throws on missing required config keys', function () {
    CreditPackDto::fromConfig('small', [
        'label' => '500 Credits',
        'credits' => 500,
    ]);
})->throws(InvalidArgumentException::class);
