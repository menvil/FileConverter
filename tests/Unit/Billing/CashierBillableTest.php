<?php

use App\Models\User;
use Laravel\Cashier\Billable;

it('uses cashier billable trait on user model', function () {
    $traits = class_uses_recursive(User::class);

    expect($traits)->toHaveKey(Billable::class);
});
