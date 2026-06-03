<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Files\StoreUploadedFileAction;
use App\Http\Requests\Api\V1\UploadFileRequest;
use App\Http\Resources\Api\V1\FileResource;
use App\Http\Resources\Api\V1\TargetFormatResource;
use App\Models\FileRecord;
use App\Support\Converters\ConverterRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class FileController
{
    public function __construct(
        private readonly ConverterRegistry $registry,
    ) {}

    public function store(UploadFileRequest $request): JsonResponse
    {
        $fileRecord = app(StoreUploadedFileAction::class)->handle(
            user: $request->user(),
            file: $request->file('file'),
        );

        return (new FileResource($fileRecord))
            ->response()
            ->setStatusCode(201);
    }

    public function targets(Request $request, FileRecord $file): JsonResource
    {
        abort_if(
            $file->user_id !== $request->user()->id,
            403,
            'You do not own this file.',
        );

        $targets = $this->registry->targetsFor($file->extension);

        return TargetFormatResource::collection($targets);
    }
}
