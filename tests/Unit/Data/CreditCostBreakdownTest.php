<?php

declare(strict_types=1);

use App\Data\Credits\CreditCostBreakdown;

it('creates credit cost breakdown dto', function () {
    $breakdown = new CreditCostBreakdown(
        base: 1,
        size: 0,
        features: 1,
        total: 2,
        details: ['target' => 'pdf'],
    );

    expect($breakdown->total)->toBe(2);
    expect($breakdown->toArray()['base'])->toBe(1);
});

it('exposes all fields', function () {
    $breakdown = new CreditCostBreakdown(
        base: 2,
        size: 0,
        features: 0,
        total: 2,
        details: ['rule' => 'image_to_pdf'],
    );

    expect($breakdown->base)->toBe(2);
    expect($breakdown->size)->toBe(0);
    expect($breakdown->features)->toBe(0);
    expect($breakdown->total)->toBe(2);
    expect($breakdown->details)->toBe(['rule' => 'image_to_pdf']);
});

it('converts to array with all keys', function () {
    $breakdown = new CreditCostBreakdown(
        base: 1,
        size: 0,
        features: 0,
        total: 1,
        details: [],
    );

    $array = $breakdown->toArray();

    expect($array)->toHaveKeys(['base', 'size', 'features', 'total', 'details']);
    expect($array['total'])->toBe(1);
});

it('rejects negative base', function () {
    new CreditCostBreakdown(base: -1, size: 0, features: 0, total: 0, details: []);
})->throws(InvalidArgumentException::class);

it('rejects negative size', function () {
    new CreditCostBreakdown(base: 0, size: -1, features: 0, total: 0, details: []);
})->throws(InvalidArgumentException::class);

it('rejects negative features', function () {
    new CreditCostBreakdown(base: 0, size: 0, features: -1, total: 0, details: []);
})->throws(InvalidArgumentException::class);

it('rejects negative total', function () {
    new CreditCostBreakdown(base: 0, size: 0, features: 0, total: -1, details: []);
})->throws(InvalidArgumentException::class);
