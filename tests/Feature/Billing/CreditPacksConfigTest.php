<?php

it('has configured credit packs', function () {
    $packs = config('billing.credit_packs');

    expect($packs)->toHaveKeys(['small', 'medium', 'large']);
    expect($packs['small']['credits'])->toBeGreaterThan(0);
    expect($packs['small'])->toHaveKeys([
        'label',
        'credits',
        'stripe_price_id',
        'description',
    ]);
});
