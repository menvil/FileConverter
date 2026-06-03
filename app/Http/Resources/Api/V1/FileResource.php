<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\FileRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FileRecord */
final class FileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'extension' => $this->extension,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'created_at' => $this->created_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
        ];
    }
}
