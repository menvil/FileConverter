<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\ConversionStatus;
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
