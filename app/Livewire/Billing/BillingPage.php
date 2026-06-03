<?php

declare(strict_types=1);

namespace App\Livewire\Billing;

use App\Actions\Billing\BuyCreditPackAction;
use App\Actions\Billing\StartSubscriptionCheckoutAction;
use App\Billing\BillingPlanDto;
use App\Billing\BillingPlanRepository;
use App\Contracts\Billing\CreditLedger;
use App\Data\Billing\CreditPackDto;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Services\Billing\CreditPackRepository;
use App\Services\FeatureAccess\FeatureAccessService;
use App\Services\FeatureAccess\PlanLimit;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
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

    public ?string $checkoutStatus = null;

    public function mount(): void
    {
        $status = request()->query('checkout');

        if (in_array($status, ['success', 'cancelled'], true)) {
            $this->checkoutStatus = $status;
        }
    }

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

    public function getApiAccessProperty(): bool
    {
        return app(FeatureAccessService::class)->allows($this->authUser, 'api_access');
    }

    public function getBatchConversionProperty(): bool
    {
        return app(FeatureAccessService::class)->allows($this->authUser, 'batch_conversion');
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

    /** @return LengthAwarePaginator<CreditTransaction> */
    public function getTransactionsProperty()
    {
        return CreditTransaction::query()
            ->where('user_id', $this->authUser->id)
            ->latest()
            ->paginate(10);
    }

    public function startSubscriptionCheckout(string $planKey): void
    {
        $session = app(StartSubscriptionCheckoutAction::class)->handle(
            user: $this->authUser,
            planKey: $planKey,
            successUrl: route('billing', ['checkout' => 'success']),
            cancelUrl: route('billing', ['checkout' => 'cancelled']),
        );

        $this->redirect($session->url, navigate: false);
    }

    public function buyCreditPack(string $packKey): void
    {
        $session = app(BuyCreditPackAction::class)->handle(
            user: $this->authUser,
            packKey: $packKey,
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
