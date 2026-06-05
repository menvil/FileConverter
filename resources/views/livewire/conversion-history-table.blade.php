<div>
    {{-- Filters --}}
    <div class="flex flex-wrap gap-3">
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="Search files or formats..."
            class="w-full rounded-[var(--ca-radius-md)] border border-[var(--ca-border)] bg-white px-3 py-2 text-sm text-[var(--ca-text)] placeholder-[var(--ca-muted)] focus:outline-none focus:ring-2 focus:ring-[var(--ca-primary)]/30 sm:max-w-xs"
        >

        <select
            wire:model.live="status"
            class="rounded-[var(--ca-radius-md)] border border-[var(--ca-border)] bg-white px-3 py-2 text-sm text-[var(--ca-text)] focus:outline-none focus:ring-2 focus:ring-[var(--ca-primary)]/30"
        >
            @foreach ($this->statusOptions() as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>

        <select
            wire:model.live="sourceFormat"
            class="rounded-[var(--ca-radius-md)] border border-[var(--ca-border)] bg-white px-3 py-2 text-sm text-[var(--ca-text)] focus:outline-none focus:ring-2 focus:ring-[var(--ca-primary)]/30"
        >
            @foreach ($this->formatOptions() as $value => $label)
                <option value="{{ $value }}">From: {{ $label }}</option>
            @endforeach
        </select>

        <select
            wire:model.live="targetFormat"
            class="rounded-[var(--ca-radius-md)] border border-[var(--ca-border)] bg-white px-3 py-2 text-sm text-[var(--ca-text)] focus:outline-none focus:ring-2 focus:ring-[var(--ca-primary)]/30"
        >
            @foreach ($this->formatOptions() as $value => $label)
                <option value="{{ $value }}">To: {{ $label }}</option>
            @endforeach
        </select>

        <input
            type="date"
            wire:model.live="dateFrom"
            class="rounded-[var(--ca-radius-md)] border border-[var(--ca-border)] bg-white px-3 py-2 text-sm text-[var(--ca-text)] focus:outline-none focus:ring-2 focus:ring-[var(--ca-primary)]/30"
        >

        <input
            type="date"
            wire:model.live="dateTo"
            class="rounded-[var(--ca-radius-md)] border border-[var(--ca-border)] bg-white px-3 py-2 text-sm text-[var(--ca-text)] focus:outline-none focus:ring-2 focus:ring-[var(--ca-primary)]/30"
        >
    </div>

    {{-- Table --}}
    <div class="mt-4">
        @if ($jobs->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-[var(--ca-radius-lg)] border border-dashed border-[var(--ca-border)] bg-[var(--ca-surface-muted)]/40 px-6 py-12 text-center">
                @if ($this->hasActiveFilters())
                    <p class="text-base font-semibold text-[var(--ca-text)]">No conversions match your filters</p>
                    <p class="mt-1 text-sm text-[var(--ca-muted)]">Try adjusting or clearing the filters above.</p>
                @else
                    <p class="text-base font-semibold text-[var(--ca-text)]">No conversion history yet</p>
                    <p class="mt-1 text-sm text-[var(--ca-muted)]">Once you convert files, every result will appear here.</p>
                    <div class="mt-4">
                        <a
                            href="{{ route('dashboard') }}"
                            class="inline-flex items-center gap-2 rounded-[var(--ca-radius-md)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:brightness-110"
                            style="background:var(--ca-primary);"
                        >Start your first conversion</a>
                    </div>
                @endif
            </div>
        @else
            <div class="overflow-hidden rounded-[var(--ca-radius-lg)] border border-[var(--ca-border)] bg-white shadow-[var(--ca-shadow-card)]">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-[var(--ca-border)] bg-[var(--ca-surface-muted)]/40">
                            <th class="px-4 py-3 text-left font-medium text-[var(--ca-muted)]">File Name</th>
                            <th class="px-4 py-3 text-left font-medium text-[var(--ca-muted)]">From</th>
                            <th class="px-4 py-3 text-left font-medium text-[var(--ca-muted)]">To</th>
                            <th class="px-4 py-3 text-left font-medium text-[var(--ca-muted)]">Size</th>
                            <th class="px-4 py-3 text-left font-medium text-[var(--ca-muted)]">Created</th>
                            <th class="px-4 py-3 text-left font-medium text-[var(--ca-muted)]">Completed</th>
                            <th class="px-4 py-3 text-left font-medium text-[var(--ca-muted)]">Status</th>
                            <th class="px-4 py-3 text-left font-medium text-[var(--ca-muted)]">Credits</th>
                            <th class="px-4 py-3 text-left font-medium text-[var(--ca-muted)]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--ca-border)]">
                        @foreach ($jobs as $job)
                            <tr class="hover:bg-[var(--ca-surface-muted)]/40">
                                <td class="px-4 py-3 text-[var(--ca-text)]">
                                    {{ $job->sourceFile?->original_name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-[var(--ca-text)]">
                                    {{ $job->source_format ? strtoupper($job->source_format) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-[var(--ca-text)]">
                                    {{ $job->target_format ? strtoupper($job->target_format) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-[var(--ca-text)]">
                                    {{ $this->formatBytes($job->sourceFile?->size_bytes) }}
                                </td>
                                <td class="px-4 py-3 text-[var(--ca-text)]">
                                    {{ $job->created_at->format('M d, Y H:i') }}
                                </td>
                                <td class="px-4 py-3 text-[var(--ca-text)]">
                                    {{ $job->completed_at?->format('M d, Y H:i') ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <x-badge :variant="$this->statusBadgeVariant($job->status)">
                                        {{ ucfirst($job->status->value) }}
                                    </x-badge>
                                </td>
                                <td class="px-4 py-3 text-[var(--ca-text)]">
                                    @if ($job->creditCharge !== null && $job->creditCharge->captured_amount !== null)
                                        {{ $job->creditCharge->captured_amount }} {{ $job->creditCharge->captured_amount === 1 ? 'credit' : 'credits' }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        @if ($this->canDownload($job))
                                            <a
                                                href="{{ route('conversions.download', $job) }}"
                                                class="text-sm font-medium text-[var(--ca-primary)] hover:underline"
                                            >Download</a>
                                        @endif

                                        @if ($this->canConvertAgain($job))
                                            <button
                                                wire:click="convertAgain({{ $job->id }})"
                                                class="text-sm font-medium text-[var(--ca-muted)] hover:text-[var(--ca-text)] hover:underline"
                                            >Convert Again</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $jobs->links() }}
            </div>
        @endif
    </div>
</div>
