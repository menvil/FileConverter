<section>
    <h2 class="text-xl font-semibold tracking-tight text-[var(--ca-text)]">Recent Conversions</h2>

    <div class="mt-4">
        @if ($conversions->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-[var(--ca-radius-lg)] border border-dashed border-[var(--ca-border)] bg-[var(--ca-surface-muted)]/40 px-6 py-12 text-center">
                <p class="text-base font-semibold text-[var(--ca-text)]">No conversions yet</p>
                <p class="mt-1 text-sm text-[var(--ca-muted)]">Upload a file to start converting</p>
            </div>
        @else
            <div class="overflow-hidden rounded-[var(--ca-radius-lg)] border border-[var(--ca-border)] bg-white shadow-[var(--ca-shadow-card)]">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-[var(--ca-border)] bg-[var(--ca-surface-muted)]/40">
                            <th class="px-4 py-3 text-left font-medium text-[var(--ca-muted)]">File Name</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--ca-border)]">
                        @foreach ($conversions as $job)
                            <tr class="hover:bg-[var(--ca-surface-muted)]/40">
                                <td class="px-4 py-3 text-[var(--ca-text)]">
                                    {{ $job->sourceFile?->original_name ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
