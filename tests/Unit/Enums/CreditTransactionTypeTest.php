<?php

declare(strict_types=1);

use App\Enums\CreditTransactionType;

it('defines all expected credit transaction types', function () {
    $expected = ['grant', 'purchase', 'spend', 'refund', 'adjustment', 'expiration'];
    $actual = array_map(fn ($c) => $c->value, CreditTransactionType::cases());

    expect($actual)->toBe($expected);
    expect($actual)->toHaveCount(6);
});
