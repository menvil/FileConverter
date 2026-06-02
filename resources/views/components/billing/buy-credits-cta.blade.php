@props(['variant' => 'full'])

@if ($variant === 'compact')
    <a href="/billing" class="inline-flex items-center gap-1 text-sm font-medium text-[var(--ca-primary)] hover:underline">
        Buy credits
    </a>
@else
    <div class="flex flex-col items-start gap-2">
        <p class="text-sm text-[var(--ca-muted)]">Get more conversion credits</p>
        <a href="/billing" class="inline-flex items-center gap-1.5 rounded-[var(--ca-radius-md)] px-3 py-1.5 text-sm font-medium transition hover:brightness-110" style="background:var(--ca-primary);color:var(--ca-on-primary);">
            Buy credits
        </a>
    </div>
@endif
