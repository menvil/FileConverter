<?php

use App\Enums\Billing\CreditPack;

it('defines supported credit pack keys', function () {
    expect(CreditPack::Small->value)->toBe('small');
    expect(CreditPack::Medium->value)->toBe('medium');
    expect(CreditPack::Large->value)->toBe('large');
});
