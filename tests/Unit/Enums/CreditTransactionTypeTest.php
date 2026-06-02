<?php

declare(strict_types=1);

use App\Enums\CreditTransactionType;

it('defines credit transaction types', function () {
    expect(CreditTransactionType::Grant->value)->toBe('grant');
    expect(CreditTransactionType::Purchase->value)->toBe('purchase');
    expect(CreditTransactionType::Spend->value)->toBe('spend');
    expect(CreditTransactionType::Refund->value)->toBe('refund');
    expect(CreditTransactionType::Adjustment->value)->toBe('adjustment');
    expect(CreditTransactionType::Expiration->value)->toBe('expiration');
});
