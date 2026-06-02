<?php

declare(strict_types=1);

use App\Models\User;

it('creates credit account for new user automatically', function () {
    $user = User::factory()->create();

    expect($user->fresh()->creditAccount)->not->toBeNull();
    expect($user->fresh()->creditAccount->balance)->toBe(0);
});
