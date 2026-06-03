<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Support\Converters\DTO\ConverterTarget;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ConverterTarget */
final class TargetFormatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'target_format' => $this->format,
            'label' => $this->label,
            'description' => $this->description,
            'recommended' => $this->recommended,
        ];
    }
}
