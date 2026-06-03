<?php

declare(strict_types=1);

namespace App\Livewire\Billing;

use App\Billing\BillingPlanDto;
use App\Billing\BillingPlanRepository;
use App\Contracts\Billing\CreditLedger;
use App\Models\User;
use App\Services\FeatureAccess\FeatureAccessService;
use App\Services\FeatureAccess\PlanLimit;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Billing — ConvertAI')]
class BillingPage extends Component
{
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

    public function render(): View
    {
        return view('livewire.billing.billing-page');
    }
}
