<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\ConversionStatus;
use App\Models\ConversionJob;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class ConversionHistoryTable extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    public string $sourceFormat = 'all';

    public string $targetFormat = 'all';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedSourceFormat(): void
    {
        $this->resetPage();
    }

    public function updatedTargetFormat(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        if (trim((string) $this->dateFrom) === '') {
            $this->dateFrom = null;
        }
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        if (trim((string) $this->dateTo) === '') {
            $this->dateTo = null;
        }
        $this->resetPage();
    }

    public function statusOptions(): array
    {
        return [
            'all' => 'All',
            'queued' => 'Queued',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            'expired' => 'Expired',
        ];
    }

    public function formatOptions(): array
    {
        return [
            'all' => 'All formats',
            'png' => 'PNG',
            'jpg' => 'JPG',
            'webp' => 'WEBP',
            'pdf' => 'PDF',
            'gif' => 'GIF',
            'bmp' => 'BMP',
            'svg' => 'SVG',
            'tiff' => 'TIFF',
        ];
    }

    public function jobs(): LengthAwarePaginator
    {
        return ConversionJob::query()
            ->where('user_id', auth()->id())
            ->with(['sourceFile', 'resultFile', 'creditCharge'])
            ->when(trim($this->search) !== '', function (Builder $query) {
                $search = '%'.trim($this->search).'%';

                $query->where(function (Builder $query) use ($search) {
                    $query
                        ->where('source_format', 'like', $search)
                        ->orWhere('target_format', 'like', $search)
                        ->orWhereHas('sourceFile', fn (Builder $q) => $q->where('original_name', 'like', $search))
                        ->orWhereHas('resultFile', fn (Builder $q) => $q->where('original_name', 'like', $search));
                });
            })
            ->when($this->status !== 'all', fn (Builder $query) => $query->where('status', $this->status))
            ->when($this->sourceFormat !== 'all', fn (Builder $query) => $query->where('source_format', $this->sourceFormat))
            ->when($this->targetFormat !== 'all', fn (Builder $query) => $query->where('target_format', $this->targetFormat))
            ->when($this->dateFrom, fn (Builder $query) => $query->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $query) => $query->whereDate('created_at', '<=', $this->dateTo))
            ->latest()
            ->paginate(15);
    }

    public function canDownload(ConversionJob $job): bool
    {
        return $job->isCompleted()
            && $job->result_file_id !== null
            && $job->resultFile !== null
            && ! $job->resultFile->isExpired();
    }

    public function canConvertAgain(ConversionJob $job): bool
    {
        return $job->sourceFile !== null && ! $job->sourceFile->isExpired();
    }

    public function convertAgain(int $jobId): void
    {
        $job = ConversionJob::query()
            ->where('user_id', auth()->id())
            ->with('sourceFile')
            ->find($jobId);

        if (! $job || ! $this->canConvertAgain($job)) {
            $this->dispatch('toast', type: 'error', message: 'Original file expired. Upload it again to repeat this conversion.');

            return;
        }

        $this->dispatch('conversion-repeat-requested', conversionJobId: $job->id);
        $this->redirectRoute('dashboard');
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

    public function hasActiveFilters(): bool
    {
        return trim($this->search) !== ''
            || $this->status !== 'all'
            || $this->sourceFormat !== 'all'
            || $this->targetFormat !== 'all'
            || $this->dateFrom !== null
            || $this->dateTo !== null;
    }

    public function render(): View
    {
        return view('livewire.conversion-history-table', [
            'jobs' => $this->jobs(),
        ]);
    }
}
