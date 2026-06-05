<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ConversionStatus;
use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoConversionSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'demo@example.com')->first();

        if ($user === null) {
            return;
        }

        // Idempotent: only seed if the demo user has no conversion jobs yet.
        if ($user->conversionJobs()->exists()) {
            return;
        }

        // Note: seeded FileRecord rows have no corresponding physical file on disk.
        // They are safe for UI/history display but will fail if the application
        // tries to read or stream the actual file bytes (e.g. re-processing or download).
        $sourceFile = FileRecord::factory()->for($user)->create([
            'original_name' => 'sample.png',
            'extension' => 'png',
            'mime_type' => 'image/png',
            'size_bytes' => 1024,
        ]);

        $resultFile = FileRecord::factory()->for($user)->create([
            'original_name' => 'sample.jpg',
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 768,
        ]);

        // Completed job with a valid result_file_id so the download route works structurally.
        ConversionJob::factory()->for($user)->for($sourceFile, 'sourceFile')->create([
            'result_file_id' => $resultFile->id,
            'source_format' => 'png',
            'target_format' => 'jpg',
            'converter_key' => 'png_to_jpg',
            'status' => ConversionStatus::Completed,
            'progress' => 100,
        ]);

        // Failed job
        ConversionJob::factory()->for($user)->for($sourceFile, 'sourceFile')->create([
            'source_format' => 'png',
            'target_format' => 'webp',
            'converter_key' => 'png_to_webp',
            'status' => ConversionStatus::Failed,
            'progress' => 10,
            'error_code' => 'driver_error',
            'error_message' => 'Demo failed job.',
        ]);

        // Queued job
        ConversionJob::factory()->for($user)->for($sourceFile, 'sourceFile')->create([
            'source_format' => 'png',
            'target_format' => 'pdf',
            'converter_key' => 'png_to_pdf',
            'status' => ConversionStatus::Queued,
            'progress' => 0,
        ]);
    }
}
