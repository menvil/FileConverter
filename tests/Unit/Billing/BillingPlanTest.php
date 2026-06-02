<?php

use App\Enums\Plan;

it('has free pro and max billing plans', function () {
    expect(Plan::Free->value)->toBe('free');
    expect(Plan::Pro->value)->toBe('pro');
    expect(Plan::Max->value)->toBe('max');
});
