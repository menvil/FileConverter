<?php

declare(strict_types=1);

use App\Actions\Conversions\RecordConversionResultFileAction;
use App\Enums\FileStatus;
use App\Models\FileRecord;
use App\Models\User;
use App\Support\Conversions\DTO\ConversionResult;
use Illuminate\Support\Facades\Storage;

it('records conversion result as file record', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    Storage::disk('local')->put('conversions/results/output.jpg', 'fake image');

    $result = new ConversionResult(
        path: 'conversions/results/output.jpg',
        originalName: 'output.jpg',
        mimeType: 'image/jpeg',
        extension: 'jpg',
        sizeBytes: 10,
        metadata: ['width' => 100],
    );

    $file = app(RecordConversionResultFileAction::class)->handle($user, $result);

    expect($file)->toBeInstanceOf(FileRecord::class);
    expect($file->user_id)->toBe($user->id);
    expect($file->extension)->toBe('jpg');
    expect($file->stored_path)->toBe('conversions/results/output.jpg');
    expect($file->mime_type)->toBe('image/jpeg');
    expect($file->status)->toBe(FileStatus::Analyzed);
    expect($file->checksum)->toBe(hash('sha256', 'fake image'));
    expect($file->metadata_json)->toBe(['width' => 100]);
});
