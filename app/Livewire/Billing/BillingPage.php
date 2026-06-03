<?php

declare(strict_types=1);

namespace App\Livewire\Billing;

use App\Billing\BillingPlanDto;
use App\Billing\BillingPlanRepository;
use App\Billing\BillingPaymentService;
use App\Billing\Exceptions\UnknownBillingPlanException;
use App\Contracts\Billing\CreditLedger;
use App\Data\Billing\CreditPackDto;
use App\Enums\Plan;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Services\Billing\CreditPackRepository;
use App\Services\FeatureAccess\FeatureAccessService;
use App\Services\FeatureAccess\PlanLimit;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Billing — ConvertAI')]
class BillingPage extends Component
{
    use WithPagination;

    public function getAuthUserProperty(): User
    {
        /** @var User */
        return auth()->user();
    }

    public function getCreditsBalanceProperty(): int
    {
        return app(CreditLedger::class)->balance($this->authUser);
    }

    public function getPlanLimitsProperty(): PlanLimit
    {
        return app(FeatureAccessService::class)->limits($this->authUser);
    }

    /** @return BillingPlanDto[] */
    public function getPlansProperty(): array
    {
        return app(BillingPlanRepository::class)->all();
    }

    /** @return Collection<int, CreditPackDto> */
    public function getCreditPacksProperty(): Collection
    {
        return app(CreditPackRepository::class)->all();
    }

    /** @return \Illuminate\Pagination\LengthAwarePaginator<CreditTransaction> */
    public function getTransactionsProperty()
    {
        return CreditTransaction::query()
            ->where('user_id', $this->authUser->id)
            ->latest()
            ->paginate(10);
    }

    public function startSubscriptionCheckout(string $planKey): void
    {
        try {
            $plan = Plan::from($planKey);
        } catch (\ValueError) {
            throw ValidationException::withMessages(['plan' => 'Invalid plan selected.']);
        }

        if ($plan === Plan::Free) {
            throw ValidationException::withMessages(['plan' => 'Free plan does not require checkout.']);
        }

        if ($this->authUser->plan === $plan) {
            throw ValidationException::withMessages(['plan' => 'You are already on this plan.']);
        }

        $billingPlan = app(BillingPlanRepository::class)->findOrFail($planKey);

        $session = app(BillingPaymentService::class)->createSubscriptionCheckout(
            user: $this->authUser,
            plan: $billingPlan,
            successUrl: route('billing', ['checkout' => 'success']),
            cancelUrl: route('billing', ['checkout' => 'cancelled']),
        );

        $this->redirect($session->url, navigate: false);
    }

    public function buyCreditPack(string $packKey): void
    {
        $pack = app(CreditPackRepository::class)->find($packKey);

        if ($pack === null) {
            throw ValidationException::withMessages(['pack' => 'This credit pack is no longer available.']);
        }

        $session = app(BillingPaymentService::class)->createCreditPackCheckout(
            user: $this->authUser,
            pack: $pack,
            successUrl: route('billing', ['checkout' => 'success']),
            cancelUrl: route('billing', ['checkout' => 'cancelled']),
        );

        $this->redirect($session->url, navigate: false);
    }

    public function openCustomerPortal(): void
    {
        throw ValidationException::withMessages(['portal' => 'Billing portal will be available after payment provider setup.']);
    }

    public function render(): View
    {
        return view('livewire.billing.billing-page');
    }
}
