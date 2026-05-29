<?php

use App\Actions\Files\StoreUploadedFileAction;
use App\Models\FileRecord;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('stores uploaded file for the provided user', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $other = User::factory()->create();

    $record = app(StoreUploadedFileAction::class)->handle(
        $user,
        UploadedFile::fake()->image('image.png'),
    );

    expect($record->user_id)->toBe($user->id);
    expect($record->user_id)->not->toBe($other->id);
});

it('scopes file records to owner', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $own = FileRecord::factory()->for($user)->create();
    FileRecord::factory()->for($other)->create();

    expect(FileRecord::query()->forUser($user)->pluck('id')->all())
        ->toBe([$own->id]);
});
