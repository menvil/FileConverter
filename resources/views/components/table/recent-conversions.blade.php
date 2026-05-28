@props([
    'rows' => [],
    'tabs' => [
        ['label' => 'All', 'count' => 18],
        ['label' => 'Completed', 'count' => 16],
        ['label' => 'Processing', 'count' => 1],
        ['label' => 'Starred', 'count' => 3],
    ],
    'activeTab' => 'All',
    'showingFrom' => 1,
    'showingTo' => 8,
    'total' => 18,
    'currentPage' => 1,
    'totalPages' => 3,
])

<section
    class="overflow-hidden rounded-[var(--ca-radius-lg)] border bg-white shadow-[var(--ca-shadow-card)]"
    style="border-color:var(--ca-border);"
>
    <header class="flex flex-wrap items-center justify-between gap-4 px-6 py-4">
        <div>
            <h2 class="text-xl font-semibold text-[var(--ca-text)]">Recent Conversions</h2>
            <p class="text-sm text-[var(--ca-muted)]">Showing {{ $showingFrom }}–{{ $showingTo }} of {{ $total }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <x-table.tabs :tabs="$tabs" :active="$activeTab" />
            <label class="relative inline-flex items-center">
                <span class="sr-only">Search</span>
                <svg class="absolute left-3 h-4 w-4 text-[var(--ca-muted)]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 3a6 6 0 1 0 3.85 10.62l3.27 3.26a.75.75 0 1 0 1.06-1.06l-3.27-3.26A6 6 0 0 0 9 3Zm0 1.5a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9Z" clip-rule="evenodd"/></svg>
                <input
                    type="search"
                    placeholder="Search files, formats…"
                    class="w-64 rounded-full border bg-white py-2 pl-9 pr-3 text-sm ca-focus-ring"
                    style="border-color:var(--ca-border);"
                />
            </label>
        </div>
    </header>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="border-y text-xs uppercase tracking-wide text-[var(--ca-muted)]" style="border-color:var(--ca-border);background:color-mix(in srgb, var(--ca-surface-muted) 60%, var(--ca-surface));">
                <tr>
                    <th class="px-6 py-3 font-semibold">File Name</th>
                    <th class="px-6 py-3 font-semibold">From</th>
                    <th class="px-6 py-3 font-semibold">To</th>
                    <th class="px-6 py-3 font-semibold">Size</th>
                    <th class="px-6 py-3 font-semibold">Date</th>
                    <th class="px-6 py-3 font-semibold">Status</th>
                    <th class="px-6 py-3 text-right font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    @php
                        $status = $row['status'] ?? 'completed';
                        $statusVariant = match ($status) {
                            'processing' => 'warning',
                            'failed' => 'danger',
                            default => 'success',
                        };
                        $starred = $row['starred'] ?? false;
                    @endphp
                    <tr class="border-b last:border-b-0" style="border-color:var(--ca-border);">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <x-file-icon :format="$row['from']" size="sm" />
                                <span class="font-medium text-[var(--ca-text)]">{{ $row['name'] }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 font-medium text-[var(--ca-text)]">{{ strtoupper($row['from']) }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-1 font-medium" style="color:var(--ca-primary);">
                                <span aria-hidden="true">→</span>
                                <span>{{ strtoupper($row['to']) }}</span>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-[var(--ca-muted)]">{{ $row['size'] }}</td>
                        <td class="px-6 py-4 text-[var(--ca-muted)]">{{ $row['date'] }}</td>
                        <td class="px-6 py-4">
                            @if ($status === 'processing')
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium" style="background:color-mix(in srgb, var(--ca-warning) 14%, var(--ca-surface));color:#92400e;">
                                    <span aria-hidden="true" class="inline-block h-1.5 w-1.5 rounded-full" style="background:var(--ca-warning);"></span>
                                    Processing
                                </span>
                            @else
                                <x-badge :variant="$statusVariant">{{ ucfirst($status) }}</x-badge>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-1 text-[var(--ca-muted)]">
                                @if ($status === 'processing')
                                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full hover:bg-[var(--ca-surface-muted)] ca-focus-ring" aria-label="Refresh">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 10a7 7 0 0 1 12-5l2-1v5h-5l2-2a5 5 0 1 0 1 4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                @else
                                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full hover:bg-[var(--ca-surface-muted)] ca-focus-ring" aria-label="Download">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 3v10m0 0-4-4m4 4 4-4M4 17h12" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                @endif
                                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full hover:bg-[var(--ca-surface-muted)] ca-focus-ring" aria-label="Open">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 5h4M5 5v4M5 5l5 5M15 15h-4M15 15v-4M15 15l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                                <button type="button" @class([
                                    'inline-flex h-8 w-8 items-center justify-center rounded-full hover:bg-[var(--ca-surface-muted)] ca-focus-ring',
                                ]) aria-label="{{ $starred ? 'Unstar' : 'Star' }}">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="{{ $starred ? '#f59e0b' : 'none' }}" stroke="{{ $starred ? '#f59e0b' : 'currentColor' }}" stroke-width="1.8" aria-hidden="true"><path d="M10 2.5l2.4 4.86 5.36.78-3.88 3.78.92 5.35L10 14.77l-4.8 2.5.92-5.35L2.24 8.14l5.36-.78L10 2.5Z" stroke-linejoin="round"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <x-table.empty-state
                                title="No conversions yet"
                                message="Upload a file and your conversion history will appear here."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <footer class="flex flex-wrap items-center justify-between gap-3 border-t px-6 py-4" style="border-color:var(--ca-border);">
        <span class="text-sm text-[var(--ca-muted)]">Page {{ $currentPage }} of {{ $totalPages }}</span>
        <nav class="flex items-center gap-1" aria-label="Pagination">
            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full border bg-white text-[var(--ca-muted)] ca-focus-ring disabled:opacity-40" style="border-color:var(--ca-border);" @disabled($currentPage <= 1) aria-label="Previous page">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.79 14.77a.75.75 0 0 1-1.06.02l-4.5-4.25a.75.75 0 0 1 0-1.08l4.5-4.25a.75.75 0 0 1 1.04 1.08L8.83 10l3.94 3.71a.75.75 0 0 1 .02 1.06Z"/></svg>
            </button>
            @for ($i = 1; $i <= $totalPages; $i++)
                <button type="button" @class([
                    'inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold ca-focus-ring',
                    'ca-gradient-primary text-white' => $i === $currentPage,
                    'border bg-white text-[var(--ca-text)]' => $i !== $currentPage,
                ]) @if ($i !== $currentPage) style="border-color:var(--ca-border);" @endif>{{ $i }}</button>
            @endfor
            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full border bg-white text-[var(--ca-muted)] ca-focus-ring disabled:opacity-40" style="border-color:var(--ca-border);" @disabled($currentPage >= $totalPages) aria-label="Next page">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7.21 5.23a.75.75 0 0 1 1.06-.02l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.04-1.08L11.17 10 7.23 6.29a.75.75 0 0 1-.02-1.06Z"/></svg>
            </button>
        </nav>
    </footer>
</section>
