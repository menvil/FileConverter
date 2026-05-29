<?php

namespace Database\Factories;

use App\Enums\FileStatus;
use App\Models\FileRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FileRecord>
 */
class FileRecordFactory extends Factory
{
    protected $model = FileRecord::class;

    public function definition(): array
    {
        $extension = fake()->randomElement(['png', 'jpg', 'webp', 'pdf']);
        $mime = match ($extension) {
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
        };

        return [
            'user_id' => User::factory(),
            'original_name' => fake()->slug().'.'.$extension,
            'stored_path' => 'uploads/'.fake()->uuid().'.'.$extension,
            'mime_type' => $mime,
            'extension' => $extension,
            'size_bytes' => fake()->numberBetween(1000, 5_000_000),
            'checksum' => hash('sha256', fake()->uuid()),
            'metadata_json' => [],
            'status' => FileStatus::Uploaded,
            'expires_at' => now()->addDay(),
        ];
    }
}
