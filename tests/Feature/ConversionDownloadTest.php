<?php

declare(strict_types=1);

use App\Enums\ConversionStatus;
use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

it('allows owner to download completed conversion result', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    Storage::disk('local')->put('conversions/result.jpg', 'fake-image-data');

    $resultFile = FileRecord::factory()->for($user)->create([
        'stored_path' => 'conversions/result.jpg',
        'original_name' => 'result.jpg',
        'mime_type' => 'image/jpeg',
        'expires_at' => now()->addHour(),
    ]);

    $job = ConversionJob::factory()->for($user)->completed()->create([
        'result_file_id' => $resultFile->id,
    ]);

    $this->actingAs($user)
        ->get(route('conversions.download', $job))
        ->assertOk();
});

it('does not allow another user to download result', function () {
    Storage::fake('local');

    $owner = User::factory()->create();
    $other = User::factory()->create();

    Storage::disk('local')->put('conversions/result.jpg', 'fake-image-data');

    $resultFile = FileRecord::factory()->for($owner)->create([
        'stored_path' => 'conversions/result.jpg',
        'expires_at' => now()->addHour(),
    ]);

    $job = ConversionJob::factory()->for($owner)->completed()->create([
        'result_file_id' => $resultFile->id,
    ]);

    $this->actingAs($other)
        ->get(route('conversions.download', $job))
        ->assertForbidden();
});

it('does not allow downloading a non-completed conversion', function () {
    $user = User::factory()->create();

    $job = ConversionJob::factory()->for($user)->processing()->create();

    $this->actingAs($user)
        ->get(route('conversions.download', $job))
        ->assertNotFound();
});

it('does not allow downloading a failed conversion', function () {
    $user = User::factory()->create();

    $job = ConversionJob::factory()->for($user)->failed()->create();

    $this->actingAs($user)
        ->get(route('conversions.download', $job))
        ->assertNotFound();
});

it('does not allow downloading an expired result', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    Storage::disk('local')->put('conversions/result.jpg', 'fake-image-data');

    $resultFile = FileRecord::factory()->for($user)->create([
        'stored_path' => 'conversions/result.jpg',
        'expires_at' => now()->subHour(),
    ]);

    $job = ConversionJob::factory()->for($user)->completed()->create([
        'result_file_id' => $resultFile->id,
    ]);

    $this->actingAs($user)
        ->get(route('conversions.download', $job))
        ->assertStatus(410);
});

it('requires authentication to download', function () {
    $user = User::factory()->create();
    $job = ConversionJob::factory()->for($user)->completed()->create();

    $this->get(route('conversions.download', $job))
        ->assertRedirect(route('login'));
});
