<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Services\FeatureAccess\FeatureAccessService;
use App\Services\FeatureAccess\PlanLimit;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class UserDropdown extends Component
{
    public ?PlanLimit $planLimits = null;

    public bool $hasApiAccess = false;

    public function __construct(
        private readonly FeatureAccessService $featureAccess,
    ) {
        $user = auth()->user();

        if ($user !== null) {
            $this->planLimits = $this->featureAccess->limits($user);
            $this->hasApiAccess = $this->featureAccess->allows($user, 'api_access');
        }
    }

    public function render(): View|Closure|string
    {
        return view('components.user-dropdown');
    }
}
