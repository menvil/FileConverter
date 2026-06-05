<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\DemoConversionSeeder;
use Database\Seeders\DemoUserSeeder;

it('seeds demo conversion history records', function () {
    (new DemoUserSeeder)->run();
    (new DemoConversionSeeder)->run();

    $user = User::where('email', 'demo@example.com')->firstOrFail();

    expect($user->conversionJobs()->count())->toBeGreaterThan(0);
});

it('seeder is idempotent and does not duplicate records on re-run', function () {
    (new DemoUserSeeder)->run();
    (new DemoConversionSeeder)->run();
    $countAfterFirst = User::where('email', 'demo@example.com')->firstOrFail()->conversionJobs()->count();

    (new DemoConversionSeeder)->run();
    $countAfterSecond = User::where('email', 'demo@example.com')->firstOrFail()->conversionJobs()->count();

    expect($countAfterSecond)->toBe($countAfterFirst);
});
