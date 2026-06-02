<?php

declare(strict_types=1);

use App\Contracts\Billing\CreditLedger;

it('has credit ledger contract methods', function () {
    $reflection = new ReflectionClass(CreditLedger::class);

    expect($reflection->hasMethod('balance'))->toBeTrue();
    expect($reflection->hasMethod('grant'))->toBeTrue();
    expect($reflection->hasMethod('spend'))->toBeTrue();
    expect($reflection->hasMethod('refund'))->toBeTrue();
});
