<?php

namespace App\Actions\Files;

use App\Enums\FileStatus;
use App\Exceptions\Files\FileStorageException;
use App\Exceptions\Storage\StorageLimitExceededException;
use App\Models\FileRecord;
use App\Models\User;
use App\Services\FeatureAccess\FeatureAccessService;
use App\Services\Storage\StorageUsageService;
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
        private readonly FeatureAccessService $featureAccess,
        private readonly StorageUsageService $storageUsage,
    ) {}

    public function handle(?User $user, UploadedFile $file, ?string $guestToken = null): FileRecord
    {
        if ($user !== null) {
            $limits = $this->featureAccess->limits($user);
            $limitBytes = $limits->storageMb * 1024 * 1024;
            $usedBytes = $this->storageUsage->usedBytes($user);
            $newFileBytes = $file->getSize();

            if ($usedBytes + $newFileBytes > $limitBytes) {
                throw StorageLimitExceededException::make($limits->storageMb, $usedBytes, $newFileBytes);
            }
        }

        $format = $this->formatDetector->detect($file);
        $metadata = $this->metadataExtractor->extract($file, $format);

        $disk = Storage::disk('local');
        $storageDir = $user !== null ? "uploads/{$user->id}" : 'uploads/guests';
        $path = $file->store($storageDir, 'local');

        if (! is_string($path) || $path === '') {
            throw FileStorageException::cannotStore($file->getClientOriginalName());
        }

        try {
            $size = $disk->size($path);
            $absolutePath = $disk->path($path);
            $checksum = is_string($absolutePath) && $absolutePath !== ''
                ? hash_file('sha256', $absolutePath)
                : false;

            if ($size === false || $checksum === false) {
                throw FileStorageException::cannotStore($file->getClientOriginalName());
            }

            return $this->recordCreator->create([
                'user_id' => $user?->id,
                'guest_token' => $guestToken,
                'original_name' => $file->getClientOriginalName(),
                'stored_path' => $path,
                'mime_type' => $file->getMimeType(),
                'extension' => $format,
                'size_bytes' => $size,
                'checksum' => $checksum,
                'metadata_json' => $metadata,
                'status' => FileStatus::Analyzed,
                'expires_at' => $this->expirationPolicy->forUploadedFile($user),
            ]);
        } catch (Throwable $e) {
            $disk->delete($path);

            if ($e instanceof FileStorageException) {
                throw $e;
            }

            throw FileStorageException::cannotCreateRecord($file->getClientOriginalName(), previous: $e);
        }
    }
}
