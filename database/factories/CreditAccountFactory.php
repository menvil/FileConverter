<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CreditAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditAccount>
 */
class CreditAccountFactory extends Factory
{
    protected $model = CreditAccount::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'balance' => 0,
        ];
    }
}
