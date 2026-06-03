<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\ConverterResource;
use App\Support\Conversions\Exceptions\UnsupportedConversionException;
use App\Support\Converters\ConverterRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ConverterController
{
    public function __construct(
        private readonly ConverterRegistry $registry,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return ConverterResource::collection($this->registry->all());
    }

    public function schema(string $source, string $target): JsonResponse
    {
        $converter = $this->registry->find($source, $target);

        if ($converter === null) {
            throw UnsupportedConversionException::forPair($source, $target);
        }

        return response()->json([
            'data' => [
                'source_format' => $converter->sourceFormat(),
                'target_format' => $converter->targetFormat(),
                'options' => $converter->optionsSchema(),
            ],
        ]);
    }
}
