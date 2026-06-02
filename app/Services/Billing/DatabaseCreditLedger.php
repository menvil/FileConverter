<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Contracts\Billing\CreditLedger;
use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use LogicException;

final class DatabaseCreditLedger implements CreditLedger
{
    public function balance(User $user): int
    {
        throw new LogicException('Not implemented yet.');
    }

    public function grant(User $user, int $amount, string $reason, array $meta = [], ?Model $source = null): CreditTransaction
    {
        throw new LogicException('Not implemented yet.');
    }

    public function spend(User $user, int $amount, string $reason, array $meta = [], ?Model $source = null): CreditTransaction
    {
        throw new LogicException('Not implemented yet.');
    }

    public function refund(User $user, int $amount, string $reason, array $meta = [], ?Model $source = null): CreditTransaction
    {
        throw new LogicException('Not implemented yet.');
    }
}
