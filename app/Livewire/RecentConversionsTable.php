<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\ConversionStatus;
use App\Models\ConversionJob;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class RecentConversionsTable extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public int $perPage = 10;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $conversions = ConversionJob::query()
            ->where('user_id', auth()->id())
            ->with(['sourceFile', 'resultFile'])
            ->when($this->statusFilter !== 'all', fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->when($this->search !== '', function (Builder $query) {
                $search = trim($this->search);

                $query->where(function (Builder $query) use ($search) {
                    $query->where('source_format', 'like', "%{$search}%")
                        ->orWhere('target_format', 'like', "%{$search}%")
                        ->orWhereHas('sourceFile', fn (Builder $q) => $q->where('original_name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.recent-conversions-table', [
            'conversions' => $conversions,
        ]);
    }

    public function convertAgain(int $conversionJobId): void
    {
        $job = ConversionJob::query()
            ->where('user_id', auth()->id())
            ->find($conversionJobId);

        if (! $job) {
            return;
        }

        $this->dispatch('conversion-repeat-requested', conversionJobId: $job->id);
    }

    public function statusBadgeClasses(ConversionStatus $status): string
    {
        return match ($status) {
            ConversionStatus::Queued => 'bg-indigo-100 text-indigo-700',
            ConversionStatus::Processing => 'bg-yellow-100 text-yellow-700',
            ConversionStatus::Completed => 'bg-green-100 text-green-700',
            ConversionStatus::Failed => 'bg-red-100 text-red-700',
            ConversionStatus::Cancelled, ConversionStatus::Expired, ConversionStatus::Draft => 'bg-gray-100 text-gray-600',
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
}
