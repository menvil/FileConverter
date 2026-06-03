<?php

declare(strict_types=1);

namespace App\Support\Api;

use Illuminate\Http\JsonResponse;

final class ApiErrorResponseFactory
{
    public function make(string $code, string $message, int $status = 400, array $details = []): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ], $status);
    }
}
