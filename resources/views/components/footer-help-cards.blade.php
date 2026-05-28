@php
    $cards = [
        [
            'title' => 'Help Center',
            'description' => 'Browse guides and FAQs',
            'cta' => 'Get help with common topics',
        ],
        [
            'title' => 'Contact Support',
            'description' => 'Submit a ticket or chat with us',
            'cta' => 'We typically reply in minutes',
        ],
        [
            'title' => 'Refer a Friend',
            'description' => 'Share your link and earn rewards',
            'cta' => 'Invite friends and earn credits',
        ],
    ];
@endphp

<section aria-label="Help and support" class="grid gap-4 md:grid-cols-3">
    @foreach ($cards as $card)
        <x-card variant="interactive">
            <h3 class="text-base font-semibold text-[var(--ca-text)]">{{ $card['title'] }}</h3>
            <p class="mt-1 text-sm text-[var(--ca-muted)]">{{ $card['description'] }}</p>
            <p class="mt-3 text-xs text-[var(--ca-muted)]">{{ $card['cta'] }}</p>
        </x-card>
    @endforeach
</section>
