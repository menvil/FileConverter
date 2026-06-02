<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\View\View;
use Livewire\Component;

class RecentConversionsTable extends Component
{
    public function render(): View
    {
        return view('livewire.recent-conversions-table');
    }
}
