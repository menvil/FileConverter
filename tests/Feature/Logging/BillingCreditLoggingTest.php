<?php

declare(strict_types=1);

use App\Contracts\Billing\CreditLedger;
use App\Models\User;
use Illuminate\Support\Facades\Log;

it('logs credit grant with context', function () {
    Log::spy();

    $user = User::factory()->create();

    app(CreditLedger::class)->grant($user, 10, 'starter_grant');

    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $message, array $context) => $message === 'credits.granted'
            && $context['user_id'] === $user->id
            && $context['amount'] === 10
        );
});

it('logs credit spend with context', function () {
    Log::spy();

    $user = User::factory()->create();
    app(CreditLedger::class)->grant($user, 10, 'setup');

    app(CreditLedger::class)->spend($user, 2, 'conversion_completed');

    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $message, array $context) => $message === 'credits.spent'
            && $context['user_id'] === $user->id
            && $context['amount'] === 2
        );
});

it('logs credit refund with context', function () {
    Log::spy();

    $user = User::factory()->create();
    app(CreditLedger::class)->grant($user, 10, 'setup');
    app(CreditLedger::class)->spend($user, 5, 'conversion_completed');

    app(CreditLedger::class)->refund($user, 3, 'conversion_refund');

    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $message, array $context) => $message === 'credits.refunded'
            && $context['user_id'] === $user->id
            && $context['amount'] === 3
        );
});
