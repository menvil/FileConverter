<?php

namespace App\Actions\Files;

use App\Enums\FileStatus;
use App\Exceptions\Files\FileStorageException;
use App\Models\FileRecord;
use App\Models\User;
use App\Support\Files\FileExpirationPolicy;
use App\Support\Files\FileFormatDetector;
use App\Support\Files\FileRecordCreator;
use App\Support\Files\ImageMetadataExtractor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class StoreUploadedFileAction
{
    public function __construct(
        private readonly FileFormatDetector $formatDetector,
        private readonly ImageMetadataExtractor $metadataExtractor,
        private readonly FileExpirationPolicy $expirationPolicy,
        private readonly FileRecordCreator $recordCreator,
    ) {}

    public function handle(User $user, UploadedFile $file): FileRecord
    {
        $format = $this->formatDetector->detect($file);
        $metadata = $this->metadataExtractor->extract($file, $format);

        $disk = Storage::disk('local');
        $path = $file->store("uploads/{$user->id}", 'local');

        if (! is_string($path) || $path === '') {
            throw FileStorageException::cannotStore($file->getClientOriginalName());
        }

        try {
            return $this->recordCreator->create([
                'user_id' => $user->id,
                'original_name' => $file->getClientOriginalName(),
                'stored_path' => $path,
                'mime_type' => $file->getMimeType(),
                'extension' => $format,
                'size_bytes' => $disk->size($path),
                'checksum' => hash_file('sha256', $disk->path($path)),
                'metadata_json' => $metadata,
                'status' => FileStatus::Analyzed,
                'expires_at' => $this->expirationPolicy->forUploadedFile($user),
            ]);
        } catch (Throwable $e) {
            $disk->delete($path);

            throw FileStorageException::cannotCreateRecord($file->getClientOriginalName(), previous: $e);
        }
    }
}
