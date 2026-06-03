<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Conversions\CreateConversionJobAction;
use App\Actions\Conversions\EstimateConversionFlowAction;
use App\Enums\ConversionStatus;
use App\Http\Requests\Api\V1\CreateConversionRequest;
use App\Http\Requests\Api\V1\EstimateConversionRequest;
use App\Http\Resources\Api\V1\ConversionResource;
use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Support\Api\ApiOwnershipGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

final class ConversionController
{
    public function __construct(
        private readonly EstimateConversionFlowAction $estimateFlow,
        private readonly ApiOwnershipGuard $ownershipGuard,
    ) {}

    public function estimate(EstimateConversionRequest $request): JsonResponse
    {
        $result = $this->estimateFlow->handle(
            user: $request->user(),
            fileId: $request->integer('file_id'),
            targetFormat: $request->string('target_format')->toString(),
            options: $request->array('options', []),
        );

        return response()->json([
            'data' => [
                'amount' => $result->amount,
                'breakdown' => $result->breakdown,
                'has_enough_credits' => $result->hasEnoughCredits,
            ],
        ]);
    }

    public function store(CreateConversionRequest $request): JsonResponse
    {
        $file = FileRecord::findOrFail($request->integer('file_id'));
        $this->ownershipGuard->ensureFileOwner($request->user(), $file);

        $job = app(CreateConversionJobAction::class)->handle(
            user: $request->user(),
            sourceFile: $file,
            targetFormat: $request->string('target_format')->toString(),
            options: $request->array('options', []),
        );

        return (new ConversionResource($job))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, int $conversionId): JsonResponse
    {
        $conversion = ConversionJob::findOrFail($conversionId);
        $this->ownershipGuard->ensureConversionOwner($request->user(), $conversion);

        return response()->json(['data' => (new ConversionResource($conversion))->resolve()]);
    }

    public function download(Request $request, int $conversionId): mixed
    {
        $conversion = ConversionJob::findOrFail($conversionId);
        $this->ownershipGuard->ensureConversionOwner($request->user(), $conversion);

        if ($conversion->status !== ConversionStatus::Completed) {
            return response()->json([
                'error' => [
                    'code' => 'result_not_ready',
                    'message' => 'Conversion is not yet complete.',
                    'details' => [],
                ],
            ], 422);
        }

        $resultFile = $conversion->resultFile;

        if ($resultFile === null) {
            return response()->json([
                'error' => [
                    'code' => 'result_not_found',
                    'message' => 'Conversion result file not found.',
                    'details' => [],
                ],
            ], 404);
        }

        if ($resultFile->isExpired()) {
            return response()->json([
                'error' => [
                    'code' => 'result_expired',
                    'message' => 'This conversion result has expired and can no longer be downloaded.',
                    'details' => [
                        'expired_at' => $resultFile->expires_at?->toIso8601String(),
                    ],
                ],
            ], 410);
        }

        if (! Storage::disk('local')->exists($resultFile->stored_path)) {
            return response()->json([
                'error' => [
                    'code' => 'result_not_found',
                    'message' => 'Conversion result file not found.',
                    'details' => [],
                ],
            ], 404);
        }

        return Storage::disk('local')->download(
            $resultFile->stored_path,
            $resultFile->original_name,
        );
    }
}
