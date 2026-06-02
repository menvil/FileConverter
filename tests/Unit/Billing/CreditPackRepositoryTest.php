<?php

declare(strict_types=1);

use App\Data\Billing\CreditPackDto;
use App\Services\Billing\CreditPackRepository;

it('returns all credit packs', function () {
    $packs = app(CreditPackRepository::class)->all();

    expect($packs)->not->toBeEmpty();
    expect($packs->first())->toBeInstanceOf(CreditPackDto::class);
});

it('finds credit pack by key', function () {
    $pack = app(CreditPackRepository::class)->findOrFail('small');

    expect($pack->key)->toBe('small');
});

it('throws for unknown credit pack key', function () {
    app(CreditPackRepository::class)->findOrFail('unknown');
})->throws(InvalidArgumentException::class);

it('finds credit pack by stripe price id', function () {
    config()->set('billing.credit_packs.small.stripe_price_id', 'price_small');

    $pack = app(CreditPackRepository::class)->findByStripePriceId('price_small');

    expect($pack?->key)->toBe('small');
});

it('returns null for unknown stripe price id', function () {
    $pack = app(CreditPackRepository::class)->findByStripePriceId('price_nonexistent');

    expect($pack)->toBeNull();
});
