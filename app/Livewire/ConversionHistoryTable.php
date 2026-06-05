<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\ConversionStatus;
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

    public function statusBadgeVariant(ConversionStatus $status): string
    {
        return match ($status) {
            ConversionStatus::Queued => 'purple',
            ConversionStatus::Processing => 'warning',
            ConversionStatus::Completed => 'success',
            ConversionStatus::Failed => 'danger',
            default => 'neutral',
        };
    }

    public function formatBytes(?int $bytes): string
    {
        if ($bytes === null) {
            return '—';
        }

        if ($bytes >= 1_000_000_000) {
            return round($bytes / 1_000_000_000, 1).' GB';
        }

        if ($bytes >= 1_000_000) {
            return round($bytes / 1_000_000, 1).' MB';
        }

        if ($bytes >= 1_000) {
            return round($bytes / 1_000).' KB';
        }

        return $bytes.' B';
    }

    public function render(): View
    {
        return view('livewire.conversion-history-table', [
            'jobs' => $this->jobs(),
        ]);
    }
}
