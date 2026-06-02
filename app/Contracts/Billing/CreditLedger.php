<?php

declare(strict_types=1);

namespace App\Contracts\Billing;

use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

interface CreditLedger
{
    public function balance(User $user): int;

    public function grant(User $user, int $amount, string $reason, array $meta = [], ?Model $source = null): CreditTransaction;

    public function spend(User $user, int $amount, string $reason, array $meta = [], ?Model $source = null): CreditTransaction;

    public function refund(User $user, int $amount, string $reason, array $meta = [], ?Model $source = null): CreditTransaction;
}
