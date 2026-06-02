<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Contracts\Billing\CreditLedger;
use App\Enums\CreditTransactionType;
use App\Exceptions\Billing\InvalidCreditAmountException;
use App\Models\CreditAccount;
use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class DatabaseCreditLedger implements CreditLedger
{
    public function balance(User $user): int
    {
        return (int) $user->creditAccount()->firstOrCreate(['user_id' => $user->id], ['balance' => 0])->balance;
    }

    public function grant(User $user, int $amount, string $reason, array $meta = [], ?Model $source = null): CreditTransaction
    {
        return DB::transaction(function () use ($user, $amount, $reason, $meta, $source) {
            if ($amount <= 0) {
                throw InvalidCreditAmountException::becauseAmountMustBePositive();
            }

            $account = CreditAccount::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrCreate(['user_id' => $user->id], ['balance' => 0]);

            $account->increment('balance', $amount);
            $account->refresh();

            return CreditTransaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'balance_after' => $account->balance,
                'type' => CreditTransactionType::Grant,
                'reason' => $reason,
                'metadata_json' => $meta ?: null,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
            ]);
        });
    }

    public function spend(User $user, int $amount, string $reason, array $meta = [], ?Model $source = null): CreditTransaction
    {
        throw new \LogicException('Not implemented yet.');
    }

    public function refund(User $user, int $amount, string $reason, array $meta = [], ?Model $source = null): CreditTransaction
    {
        throw new \LogicException('Not implemented yet.');
    }
}
