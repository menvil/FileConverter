<?php

use App\Actions\Files\StoreUploadedFileAction;
use App\Exceptions\Files\FileStorageException;
use App\Models\FileRecord;
use App\Models\User;
use App\Support\Files\FileRecordCreator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('removes the physical file when file record creation fails', function () {
    Storage::fake('local');

    $this->app->bind(FileRecordCreator::class, function () {
        return new class extends FileRecordCreator
        {
            public function create(array $attributes): FileRecord
            {
                throw new RuntimeException('simulated db failure');
            }
        };
    });

    $user = User::factory()->create();

    try {
        app(StoreUploadedFileAction::class)->handle(
            $user,
            UploadedFile::fake()->image('image.png'),
        );
        $this->fail('Expected FileStorageException to be thrown.');
    } catch (FileStorageException $e) {
        // expected
    }

    expect(FileRecord::query()->count())->toBe(0);

    $storedFiles = Storage::disk('local')->allFiles("uploads/{$user->id}");
    expect($storedFiles)->toBeEmpty();
});
