<?php

declare(strict_types=1);

namespace App\Actions\Conversions;

use App\Enums\FileStatus;
use App\Models\FileRecord;
use App\Models\User;
use App\Support\Conversions\DTO\ConversionResult;
use App\Support\Files\FileExpirationPolicy;
use Illuminate\Support\Facades\Storage;

final class RecordConversionResultFileAction
{
    public function __construct(
        private readonly FileExpirationPolicy $expirationPolicy,
    ) {}

    public function handle(User $user, ConversionResult $result): FileRecord
    {
        $contents = (string) Storage::disk('local')->get($result->path);

        return FileRecord::create([
            'user_id' => $user->id,
            'original_name' => $result->originalName,
            'stored_path' => $result->path,
            'mime_type' => $result->mimeType,
            'extension' => $result->extension,
            'size_bytes' => $result->sizeBytes,
            'checksum' => hash('sha256', $contents),
            'metadata_json' => $result->metadata,
            'status' => FileStatus::Analyzed,
            'expires_at' => $this->expirationPolicy->forResultFile($user),
        ]);
    }
}
