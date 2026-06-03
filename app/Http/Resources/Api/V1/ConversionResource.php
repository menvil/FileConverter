<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\ConversionJob;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ConversionJob */
final class ConversionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'progress' => $this->progress,
            'source_format' => $this->source_format,
            'target_format' => $this->target_format,
            'error_code' => $this->error_code,
            'error_message' => $this->error_message,
            'created_at' => $this->created_at?->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
        ];
    }
}
