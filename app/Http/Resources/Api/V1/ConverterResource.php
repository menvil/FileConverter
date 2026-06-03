<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Support\Converters\Contracts\Converter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Converter */
final class ConverterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->key(),
            'source_format' => $this->sourceFormat(),
            'target_format' => $this->targetFormat(),
            'label' => $this->label(),
            'description' => $this->description(),
        ];
    }
}
