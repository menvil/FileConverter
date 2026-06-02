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
