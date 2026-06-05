<?php

declare(strict_types=1);

use App\Models\ConversionCreditCharge;
use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Models\User;

it('renders full history page with table filters and actions', function () {
    $user = User::factory()->create();

    $source = FileRecord::factory()->for($user)->create([
        'original_name' => 'final-smoke.png',
    ]);

    $result = FileRecord::factory()->for($user)->create([
        'original_name' => 'final-smoke.jpg',
        'expires_at' => now()->addDay(),
    ]);

    $job = ConversionJob::factory()->for($user)->completed()->create([
        'source_file_id' => $source->id,
        'result_file_id' => $result->id,
        'source_format' => 'png',
        'target_format' => 'jpg',
    ]);

    ConversionCreditCharge::factory()->for($user)->for($job, 'conversionJob')->create([
        'captured_amount' => 1,
        'status' => 'captured',
    ]);

    $this->actingAs($user)
        ->get('/history')
        ->assertOk()
        ->assertSee('Conversion History')
        ->assertSee('final-smoke.png')
        ->assertSee('PNG')
        ->assertSee('JPG')
        ->assertSee('Completed')
        ->assertSee('1 credit');
});

it('does not leak other user conversion data on history page', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $ownFile = FileRecord::factory()->for($user)->create([
        'original_name' => 'my-file.png',
    ]);

    $otherFile = FileRecord::factory()->for($otherUser)->create([
        'original_name' => 'their-file.png',
    ]);

    ConversionJob::factory()->for($user)->for($ownFile, 'sourceFile')->create();
    ConversionJob::factory()->for($otherUser)->for($otherFile, 'sourceFile')->create();

    $this->actingAs($user)
        ->get('/history')
        ->assertOk()
        ->assertSee('my-file.png')
        ->assertDontSee('their-file.png');
});
