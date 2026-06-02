<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ConversionStatus;
use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConversionJob>
 */
class ConversionJobFactory extends Factory
{
    protected $model = ConversionJob::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            // Keep the source file owned by the same user as the job.
            'source_file_id' => fn (array $attributes) => FileRecord::factory()->state([
                'user_id' => $attributes['user_id'],
            ]),
            'result_file_id' => null,
            'source_format' => 'png',
            'target_format' => 'jpg',
            'converter_key' => 'png_to_jpg',
            'options_json' => ['quality' => 'high'],
            'status' => ConversionStatus::Queued,
            'progress' => 0,
            'error_code' => null,
            'error_message' => null,
        ];
    }

    public function queued(): static
    {
        return $this->state(['status' => ConversionStatus::Queued, 'progress' => 0]);
    }

    public function processing(): static
    {
        return $this->state([
            'status' => ConversionStatus::Processing,
            'progress' => 10,
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => ConversionStatus::Completed,
            'progress' => 100,
            'started_at' => now()->subSeconds(5),
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => ConversionStatus::Failed,
            'error_code' => 'driver_failed',
            'error_message' => 'Conversion driver encountered an error.',
            'completed_at' => now(),
        ]);
    }
}
