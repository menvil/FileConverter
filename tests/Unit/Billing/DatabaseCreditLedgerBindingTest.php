<?php

declare(strict_types=1);

use App\Contracts\Billing\CreditLedger;
use App\Services\Billing\DatabaseCreditLedger;

it('resolves credit ledger contract to database implementation', function () {
    $ledger = app(CreditLedger::class);

    expect($ledger)->toBeInstanceOf(DatabaseCreditLedger::class);
});
