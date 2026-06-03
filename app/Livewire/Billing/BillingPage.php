<?php

declare(strict_types=1);

namespace App\Livewire\Billing;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Billing — ConvertAI')]
class BillingPage extends Component
{
    public function render(): View
    {
        return view('livewire.billing.billing-page');
    }
}
