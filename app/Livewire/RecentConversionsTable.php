<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\ConversionJob;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class RecentConversionsTable extends Component
{
    public function render(): View
    {
        $conversions = ConversionJob::query()
            ->where('user_id', auth()->id())
            ->with(['sourceFile', 'resultFile'])
            ->latest()
            ->get();

        return view('livewire.recent-conversions-table', [
            'conversions' => $conversions,
        ]);
    }
}
