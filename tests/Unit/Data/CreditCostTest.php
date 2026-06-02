<?php

declare(strict_types=1);

use App\Data\Credits\CreditCost;

it('creates credit cost dto', function () {
    $cost = new CreditCost(amount: 2, breakdown: []);

    expect($cost->amount)->toBe(2);
    expect($cost->breakdown)->toBe([]);
});

it('creates credit cost with breakdown data', function () {
    $breakdown = ['base' => 2, 'total' => 2];
    $cost = new CreditCost(amount: 2, breakdown: $breakdown);

    expect($cost->amount)->toBe(2);
    expect($cost->breakdown)->toBe($breakdown);
});

it('rejects negative amount', function () {
    new CreditCost(amount: -1, breakdown: []);
})->throws(InvalidArgumentException::class);

it('allows zero amount', function () {
    $cost = new CreditCost(amount: 0, breakdown: []);

    expect($cost->amount)->toBe(0);
});
