<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Billing\CreditLedger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CreditController
{
    public function __construct(
        private readonly CreditLedger $creditLedger,
    ) {}

    public function balance(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'balance' => $this->creditLedger->balance($request->user()),
                'unit' => 'credits',
            ],
        ]);
    }
}
