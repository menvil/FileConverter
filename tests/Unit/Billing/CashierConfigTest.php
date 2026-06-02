<?php

it('has cashier stripe configuration keys', function () {
    expect(config('cashier.key'))->not->toBeNull();
});
