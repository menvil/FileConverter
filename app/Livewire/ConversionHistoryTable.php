<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\ConversionJob;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ConversionHistoryTable extends Component
{
    use WithPagination;

    public function jobs(): LengthAwarePaginator
    {
        return ConversionJob::query()
            ->where('user_id', auth()->id())
            ->with(['sourceFile', 'resultFile', 'creditCharge'])
            ->latest()
            ->paginate(15);
    }

    public function render(): View
    {
        return view('livewire.conversion-history-table', [
            'jobs' => $this->jobs(),
        ]);
    }
}
