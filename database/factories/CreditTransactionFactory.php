<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CreditTransactionType;
use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditTransaction>
 */
class CreditTransactionFactory extends Factory
{
    protected $model = CreditTransaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount' => 10,
            'balance_after' => 10,
            'type' => CreditTransactionType::Grant,
            'reason' => 'test',
            'metadata_json' => null,
        ];
    }
}
