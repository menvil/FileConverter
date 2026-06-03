<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Conversions\CreateConversionJobAction;
use App\Actions\Conversions\EstimateConversionCostAction;
use App\Contracts\Billing\CreditLedger;
use App\Enums\ConversionStatus;
use App\Http\Requests\Api\V1\CreateConversionRequest;
use App\Http\Requests\Api\V1\EstimateConversionRequest;
use App\Http\Resources\Api\V1\ConversionResource;
use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Support\Conversions\Exceptions\UnsupportedConversionException;
use App\Support\Converters\ConverterRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

final class ConversionController
{
    public function __construct(
        private readonly ConverterRegistry $registry,
        private readonly CreditLedger $creditLedger,
    ) {}

    public function estimate(EstimateConversionRequest $request): JsonResponse
    {
        $file = FileRecord::findOrFail($request->integer('file_id'));

        abort_if($file->user_id !== $request->user()->id, 403, 'You do not own this file.');

        $converter = $this->registry->find($file->extension, $request->string('target_format')->toString());

        if ($converter === null) {
            throw UnsupportedConversionException::forPair($file->extension, $request->string('target_format')->toString());
        }

        $options = $converter->validateOptions($request->array('options', []));

        $cost = app(EstimateConversionCostAction::class)->handle($file, $converter, $options);

        $balance = $this->creditLedger->balance($request->user());

        return response()->json([
            'data' => [
                'amount' => $cost->amount,
                'breakdown' => $cost->breakdown,
                'has_enough_credits' => $balance >= $cost->amount,
            ],
        ]);
    }

    public function store(CreateConversionRequest $request): JsonResponse
    {
        $file = FileRecord::findOrFail($request->integer('file_id'));

        abort_if($file->user_id !== $request->user()->id, 403, 'You do not own this file.');

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

    public function show(Request $request, ConversionJob $conversion): JsonResponse
    {
        abort_if($conversion->user_id !== $request->user()->id, 403, 'You do not own this conversion.');

        return response()->json(['data' => (new ConversionResource($conversion))->resolve()]);
    }

    public function download(Request $request, ConversionJob $conversion): mixed
    {
        abort_if($conversion->user_id !== $request->user()->id, 403, 'You do not own this conversion.');

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

        if ($resultFile === null || ! Storage::disk('local')->exists($resultFile->stored_path)) {
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
                    'message' => 'Conversion result has expired.',
                    'details' => [],
                ],
            ], 410);
        }

        return Storage::disk('local')->download(
            $resultFile->stored_path,
            $resultFile->original_name,
        );
    }
}
