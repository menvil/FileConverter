<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Contracts\Billing\CreditLedger;
use App\Enums\CreditTransactionType;
use App\Exceptions\Billing\InsufficientCreditsException;
use App\Exceptions\Billing\InvalidCreditAmountException;
use App\Models\CreditAccount;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Support\Logging\BillingLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

final class DatabaseCreditLedger implements CreditLedger
{
    public function balance(User $user): int
    {
        return $user->creditAccount()->firstOrCreate(['user_id' => $user->id], ['balance' => 0])->balance;
    }

    public function grant(User $user, int $amount, string $reason, array $meta = [], ?Model $source = null): CreditTransaction
    {
        $transaction = $this->addToBalance($user, $amount, CreditTransactionType::Grant, $reason, $meta, $source);

        try {
            app(BillingLogger::class)->creditsGranted($user, $amount, $reason);
        } catch (Throwable $e) {
            report($e);
        }

        return $transaction;
    }

    public function spend(User $user, int $amount, string $reason, array $meta = [], ?Model $source = null): CreditTransaction
    {
        $transaction = DB::transaction(function () use ($user, $amount, $reason, $meta, $source) {
            if ($amount <= 0) {
                throw InvalidCreditAmountException::becauseAmountMustBePositive();
            }

            $account = CreditAccount::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrCreate(['user_id' => $user->id], ['balance' => 0]);

            if ($account->balance < $amount) {
                throw InsufficientCreditsException::forCost($amount, $account->balance);
            }

            $account->decrement('balance', $amount);
            $account->refresh();

            return CreditTransaction::forceCreate([
                'user_id' => $user->id,
                'amount' => -$amount,
                'balance_after' => $account->balance,
                'type' => CreditTransactionType::Spend,
                'reason' => $reason,
                'metadata_json' => $meta ?: null,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
            ]);
        });

        try {
            app(BillingLogger::class)->creditsSpent($user, $amount, $reason);
        } catch (Throwable $e) {
            report($e);
        }

        return $transaction;
    }

    public function refund(User $user, int $amount, string $reason, array $meta = [], ?Model $source = null): CreditTransaction
    {
        $transaction = $this->addToBalance($user, $amount, CreditTransactionType::Refund, $reason, $meta, $source);

        try {
            app(BillingLogger::class)->creditsRefunded($user, $amount, $reason);
        } catch (Throwable $e) {
            report($e);
        }

        return $transaction;
    }

    private function addToBalance(User $user, int $amount, CreditTransactionType $type, string $reason, array $meta, ?Model $source): CreditTransaction
    {
        return DB::transaction(function () use ($user, $amount, $type, $reason, $meta, $source) {
            if ($amount <= 0) {
                throw InvalidCreditAmountException::becauseAmountMustBePositive();
            }

            $account = CreditAccount::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrCreate(['user_id' => $user->id], ['balance' => 0]);

            $account->increment('balance', $amount);
            $account->refresh();

            return CreditTransaction::forceCreate([
                'user_id' => $user->id,
                'amount' => $amount,
                'balance_after' => $account->balance,
                'type' => $type,
                'reason' => $reason,
                'metadata_json' => $meta ?: null,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
            ]);
        });
    }
}
