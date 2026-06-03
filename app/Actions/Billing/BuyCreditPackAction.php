<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Billing\BillingPaymentService;
use App\Billing\CheckoutSessionDto;
use App\Models\User;
use App\Services\Billing\CreditPackRepository;
use Illuminate\Validation\ValidationException;

final class BuyCreditPackAction
{
    public function __construct(
        private readonly CreditPackRepository $packRepository,
        private readonly BillingPaymentService $paymentService,
    ) {}

    public function handle(User $user, string $packKey, string $successUrl, string $cancelUrl): CheckoutSessionDto
    {
        $pack = $this->packRepository->find($packKey);

        if ($pack === null) {
            throw ValidationException::withMessages(['pack' => 'This credit pack is no longer available.']);
        }

        return $this->paymentService->createCreditPackCheckout(
            user: $user,
            pack: $pack,
            successUrl: $successUrl,
            cancelUrl: $cancelUrl,
        );
    }
}
