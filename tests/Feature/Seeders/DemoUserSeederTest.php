<?php

declare(strict_types=1);

use App\Contracts\Billing\CreditLedger;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;

it('seeds demo user with credits', function () {
    (new DemoUserSeeder)->run();

    $user = User::where('email', 'demo@example.com')->first();

    expect($user)->not->toBeNull();
    expect(app(CreditLedger::class)->balance($user))->toBeGreaterThan(0);
});

it('seeder is idempotent and does not create duplicate demo user or duplicate credits', function () {
    (new DemoUserSeeder)->run();

    $user = User::where('email', 'demo@example.com')->firstOrFail();
    $balanceAfterFirst = app(CreditLedger::class)->balance($user);

    (new DemoUserSeeder)->run();

    $balanceAfterSecond = app(CreditLedger::class)->balance($user);

    expect(User::where('email', 'demo@example.com')->count())->toBe(1);
    expect($balanceAfterSecond)->toBe($balanceAfterFirst);
});
