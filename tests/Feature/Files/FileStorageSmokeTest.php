<?php

use App\Actions\Files\StoreUploadedFileAction;
use App\Enums\FileStatus;
use App\Exceptions\Files\UnsupportedFileFormatException;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('stores mvp-supported image uploads', function (string $filename, string $expectedFormat) {
    Storage::fake('local');

    $user = User::factory()->create();
    $upload = UploadedFile::fake()->image($filename, 800, 600);

    $record = app(StoreUploadedFileAction::class)->handle($user, $upload);

    expect($record->extension)->toBe($expectedFormat);
    expect($record->status)->toBe(FileStatus::Analyzed);
    expect($record->metadata_json['width'])->toBe(800);
    expect($record->metadata_json['height'])->toBe(600);

    Storage::disk('local')->assertExists($record->stored_path);
})->with([
    ['image.png', 'png'],
    ['image.jpg', 'jpg'],
    ['image.jpeg', 'jpg'],
]);

it('stores pdf upload with empty metadata', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $upload = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $record = app(StoreUploadedFileAction::class)->handle($user, $upload);

    expect($record->extension)->toBe('pdf');
    expect($record->status)->toBe(FileStatus::Analyzed);
    expect($record->metadata_json)->toBeArray();
    expect($record->metadata_json)->toBeEmpty();
    expect($record->checksum)->not->toBeEmpty();

    Storage::disk('local')->assertExists($record->stored_path);
});

it('rejects an unsupported file upload in the storage pipeline', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    app(StoreUploadedFileAction::class)->handle(
        $user,
        UploadedFile::fake()->create('note.txt', 10, 'text/plain'),
    );
})->throws(UnsupportedFileFormatException::class);
