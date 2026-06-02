<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ConversionCreditChargeStatus;
use App\Models\ConversionCreditCharge;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConversionCreditCharge>
 */
class ConversionCreditChargeFactory extends Factory
{
    protected $model = ConversionCreditCharge::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'conversion_job_id' => null,
            'estimated_amount' => 1,
            'captured_amount' => 0,
            'refunded_amount' => 0,
            'status' => ConversionCreditChargeStatus::Estimated,
            'breakdown_json' => null,
        ];
    }

    public function estimated(): static
    {
        return $this->state([
            'status' => ConversionCreditChargeStatus::Estimated,
            'captured_amount' => 0,
        ]);
    }

    public function captured(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ConversionCreditChargeStatus::Captured,
            'captured_amount' => $attributes['estimated_amount'],
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => ConversionCreditChargeStatus::Failed,
            'captured_amount' => 0,
        ]);
    }
}
