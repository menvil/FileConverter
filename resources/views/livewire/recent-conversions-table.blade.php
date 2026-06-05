<section>
    <h2 class="text-xl font-semibold tracking-tight text-[var(--ca-text)]">Recent Conversions</h2>

    <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <label class="sr-only" for="recent-search">Search conversions</label>
        <input
            id="recent-search"
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="Search files, formats..."
            class="w-full rounded-[var(--ca-radius-md)] border border-[var(--ca-border)] bg-white px-3 py-2 text-sm text-[var(--ca-text)] placeholder-[var(--ca-muted)] focus:outline-none focus:ring-2 focus:ring-[var(--ca-primary)]/30 sm:max-w-xs"
        >

        <label class="sr-only" for="recent-status">Filter by status</label>
        <select
            id="recent-status"
            wire:model.live="statusFilter"
            class="rounded-[var(--ca-radius-md)] border border-[var(--ca-border)] bg-white px-3 py-2 text-sm text-[var(--ca-text)] focus:outline-none focus:ring-2 focus:ring-[var(--ca-primary)]/30"
        >
            <option value="all">All statuses</option>
            <option value="completed">Completed</option>
            <option value="processing">Processing</option>
            <option value="failed">Failed</option>
        </select>
    </div>

    <div class="mt-4">
        @if ($conversions->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-[var(--ca-radius-lg)] border border-dashed border-[var(--ca-border)] bg-[var(--ca-surface-muted)]/40 px-6 py-12 text-center">
                <p class="text-base font-semibold text-[var(--ca-text)]">No conversions yet</p>
                <p class="mt-1 text-sm text-[var(--ca-muted)]">Upload your first PNG, JPG, WEBP or PDF file to start converting.</p>
                <div class="mt-4">
                    <a
                        href="{{ route('dashboard') }}"
                        class="inline-flex items-center gap-2 rounded-[var(--ca-radius-md)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:brightness-110"
                        style="background:var(--ca-primary);"
                    >Start your first conversion</a>
                </div>
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
                            <th class="px-4 py-3 text-left font-medium text-[var(--ca-muted)]">Date</th>
                            <th class="px-4 py-3 text-left font-medium text-[var(--ca-muted)]">Status</th>
                            <th class="px-4 py-3 text-left font-medium text-[var(--ca-muted)]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--ca-border)]">
                        @foreach ($conversions as $job)
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
                                    {{ $this->formatBytes(($job->resultFile ?? $job->sourceFile)?->size_bytes) }}
                                </td>
                                <td class="px-4 py-3 text-[var(--ca-text)]">
                                    {{ $job->created_at?->format('M j, Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $this->statusBadgeClasses($job->status) }}">
                                        {{ ucfirst($job->status->value) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @php $expirationMeta = $this->expirationMetaFor($job); @endphp
                                    @if ($expirationMeta !== null)
                                        <div class="mb-1 text-xs {{ $expirationMeta['class'] }}">
                                            {{ $expirationMeta['label'] }}
                                        </div>
                                    @endif
                                    <div class="flex items-center gap-2">
                                        @if ($this->canDownload($job))
                                            <a
                                                href="{{ route('conversions.download', $job) }}"
                                                aria-label="Download {{ $job->resultFile?->original_name ?? 'result' }}"
                                                class="text-sm font-medium text-[var(--ca-primary)] hover:underline ca-focus-ring rounded"
                                            >Download</a>
                                        @endif

                                        @if ($this->canConvertAgain($job))
                                            <button
                                                type="button"
                                                wire:click="convertAgain({{ $job->id }})"
                                                aria-label="Convert {{ $job->sourceFile?->original_name ?? 'file' }} again"
                                                class="text-sm font-medium text-[var(--ca-muted)] hover:text-[var(--ca-text)] ca-focus-ring rounded"
                                            >Convert again</button>
                                        @endif

                                        <button
                                            type="button"
                                            wire:click="toggleStar({{ $job->id }})"
                                            aria-label="{{ $job->is_starred ? 'Unstar' : 'Star' }} {{ $job->sourceFile?->original_name ?? 'conversion' }}"
                                            aria-pressed="{{ $job->is_starred ? 'true' : 'false' }}"
                                            class="text-sm font-medium text-[var(--ca-muted)] hover:text-[var(--ca-text)] ca-focus-ring rounded"
                                        >{{ $job->is_starred ? 'Starred' : 'Star' }}</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if ($conversions->hasPages())
        <div class="mt-4">
            {{ $conversions->links() }}
        </div>
    @endif
</section>
