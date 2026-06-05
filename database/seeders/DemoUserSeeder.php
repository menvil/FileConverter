<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Contracts\Billing\CreditLedger;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password'),
            ],
        );

        // Ensure the credit account exists (UserObserver may have already created it).
        $user->creditAccount()->firstOrCreate(['user_id' => $user->id], ['balance' => 0]);

        // Only grant demo credits if the account has zero balance to keep seeder idempotent.
        if (app(CreditLedger::class)->balance($user) === 0) {
            app(CreditLedger::class)->grant($user, 100, 'demo_grant');
        }
    }
}
