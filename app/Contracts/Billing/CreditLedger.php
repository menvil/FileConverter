<?php

declare(strict_types=1);

namespace App\Contracts\Billing;

use App\Exceptions\Billing\InsufficientCreditsException;
use App\Exceptions\Billing\InvalidCreditAmountException;
use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

interface CreditLedger
{
    public function balance(User $user): int;

    /**
     * @throws InvalidCreditAmountException
     */
    public function grant(User $user, int $amount, string $reason, array $meta = [], ?Model $source = null): CreditTransaction;

    /**
     * @throws InvalidCreditAmountException
     * @throws InsufficientCreditsException
     */
    public function spend(User $user, int $amount, string $reason, array $meta = [], ?Model $source = null): CreditTransaction;

    /**
     * @throws InvalidCreditAmountException
     */
    public function refund(User $user, int $amount, string $reason, array $meta = [], ?Model $source = null): CreditTransaction;
}
