<x-layouts.app title="UI Kit — ConvertAI">
    @php
        $section = fn ($title) => '<h2 class="mt-12 mb-4 text-2xl font-semibold tracking-tight">'.$title.'</h2>';
    @endphp

    <header class="flex flex-col gap-2">
        <span class="text-sm uppercase tracking-wider text-[var(--ca-muted)]">Internal</span>
        <h1 class="text-3xl font-semibold tracking-tight">UI Kit</h1>
        <p class="max-w-2xl text-[var(--ca-muted)]">Reference page for all Phase 1 primitives — buttons, cards, badges, icons, stepper, form controls, and table shells. Source: <code>resources/views/components/</code>.</p>
    </header>

    {{-- Design tokens --}}
    <h2 class="mt-12 mb-4 text-2xl font-semibold tracking-tight">Design Tokens</h2>
    <x-card variant="elevated">
        <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-4">
            @php
                $swatches = [
                    ['Primary', 'var(--ca-primary)', '#7c3aed'],
                    ['Primary strong', 'var(--ca-primary-strong)', '#6d28d9'],
                    ['Accent', 'var(--ca-accent)', '#f97316'],
                    ['Success', 'var(--ca-success)', '#16a34a'],
                    ['Warning', 'var(--ca-warning)', '#f59e0b'],
                    ['Danger', 'var(--ca-danger)', '#dc2626'],
                    ['Surface', 'var(--ca-surface)', '#ffffff'],
                    ['Surface muted', 'var(--ca-surface-muted)', '#f1f5f9'],
                ];
            @endphp
            @foreach ($swatches as [$name, $cssVar, $hex])
                <div class="flex items-center gap-3 rounded-[var(--ca-radius-md)] border p-3" style="border-color:var(--ca-border);">
                    <span class="h-10 w-10 rounded-[var(--ca-radius-sm)] border" style="background:{{ $cssVar }};border-color:var(--ca-border);" aria-hidden="true"></span>
                    <div class="flex flex-col text-sm">
                        <span class="font-medium">{{ $name }}</span>
                        <span class="font-mono text-xs text-[var(--ca-muted)]">{{ $hex }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </x-card>

    {{-- Buttons --}}
    <h2 class="mt-12 mb-4 text-2xl font-semibold tracking-tight">Buttons</h2>
    <x-card variant="elevated">
        <div class="flex flex-col gap-6">
            <div>
                <p class="mb-2 text-sm font-medium text-[var(--ca-muted)]">Variants</p>
                <div class="flex flex-wrap items-center gap-3">
                    <x-button variant="primary">Primary</x-button>
                    <x-button variant="secondary">Secondary</x-button>
                    <x-button variant="ghost">Ghost</x-button>
                    <x-button variant="gradient">Gradient</x-button>
                    <x-button variant="danger">Danger</x-button>
                    <x-button :disabled="true">Disabled</x-button>
                </div>
            </div>
            <div>
                <p class="mb-2 text-sm font-medium text-[var(--ca-muted)]">Sizes</p>
                <div class="flex flex-wrap items-center gap-3">
                    <x-button size="sm">Small</x-button>
                    <x-button size="md">Medium</x-button>
                    <x-button size="lg">Large</x-button>
                </div>
            </div>
        </div>
    </x-card>

    {{-- Cards --}}
    <h2 class="mt-12 mb-4 text-2xl font-semibold tracking-tight">Cards</h2>
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <x-card>Default card</x-card>
        <x-card variant="elevated">Elevated card</x-card>
        <x-card variant="interactive">Interactive card</x-card>
        <x-card variant="gradient">Gradient card</x-card>
    </div>

    {{-- Badges --}}
    <h2 class="mt-12 mb-4 text-2xl font-semibold tracking-tight">Badges</h2>
    <x-card variant="elevated">
        <div class="flex flex-wrap items-center gap-3">
            <x-badge variant="neutral">Neutral</x-badge>
            <x-badge variant="success">Completed</x-badge>
            <x-badge variant="warning">Processing</x-badge>
            <x-badge variant="danger">Failed</x-badge>
            <x-badge variant="purple">PRO</x-badge>
            <x-badge variant="gradient">Gradient</x-badge>
            <x-badge size="sm">Small</x-badge>
        </div>
    </x-card>

    {{-- File Icons --}}
    <h2 class="mt-12 mb-4 text-2xl font-semibold tracking-tight">File Icons</h2>
    <x-card variant="elevated">
        <div class="flex flex-wrap items-center gap-4">
            <x-file-icon format="png" />
            <x-file-icon format="jpg" />
            <x-file-icon format="jpeg" />
            <x-file-icon format="webp" />
            <x-file-icon format="pdf" />
            <x-file-icon format="unknown" />
            <span class="text-sm text-[var(--ca-muted)]">— sizes: </span>
            <x-file-icon format="png" size="sm" />
            <x-file-icon format="png" size="md" />
            <x-file-icon format="png" size="lg" />
        </div>
    </x-card>

    {{-- Stepper --}}
    <h2 class="mt-12 mb-4 text-2xl font-semibold tracking-tight">Stepper</h2>
    <x-card variant="elevated">
        <div class="flex flex-col gap-6">
            <x-stepper :steps="['File', 'Format', 'Settings', 'Convert']" active="File" />
            <x-stepper :steps="['File', 'Format', 'Settings', 'Convert']" active="Settings" />
            <x-stepper :steps="['File', 'Format', 'Settings', 'Convert']" active="Convert" />
        </div>
    </x-card>

    {{-- Form Controls --}}
    <h2 class="mt-12 mb-4 text-2xl font-semibold tracking-tight">Form Controls</h2>
    <x-card variant="elevated">
        <div class="grid gap-6 md:grid-cols-2">
            <x-form.input name="filename" label="File name" placeholder="my-image" hint="Optional. Used for the downloaded file." />
            <x-form.input name="bad" label="With error" value="oops" error="This value is invalid." />
            <x-form.select name="quality" label="Quality" hint="Higher is larger.">
                <option>High</option>
                <option>Medium</option>
                <option>Low</option>
            </x-form.select>
            <x-form.segmented name="fit" :options="['cover' => 'Cover', 'contain' => 'Contain', 'fill' => 'Fill']" value="cover" label="Fit" />
            <x-form.toggle name="strip" label="Strip metadata" :checked="true" />
            <x-form.color name="bg" label="Background" value="#7c3aed" />
        </div>
    </x-card>

    {{-- User dropdown --}}
    <h2 class="mt-12 mb-4 text-2xl font-semibold tracking-tight">User Dropdown</h2>
    <x-card variant="elevated">
        <p class="mb-4 text-sm text-[var(--ca-muted)]">Click the avatar in the header to expand. Alpine-powered.</p>
        <div class="inline-block"><x-user-dropdown name="Alex Johnson" plan="Pro" :credits="240" initials="AJ" /></div>
    </x-card>

    {{-- Table shell --}}
    <h2 class="mt-12 mb-4 text-2xl font-semibold tracking-tight">Table Shell</h2>
    <div class="flex flex-col gap-6">
        <x-table.shell title="Recent Conversions" description="Your conversion history" actionLabel="View all" actionUrl="#">
            <table class="w-full text-left text-sm">
                <thead class="bg-[var(--ca-surface-muted)] text-xs uppercase text-[var(--ca-muted)]">
                    <tr>
                        <th class="px-6 py-3">File Name</th>
                        <th class="px-6 py-3">From</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-t" style="border-color:var(--ca-border);">
                        <td class="px-6 py-3"><div class="flex items-center gap-3"><x-file-icon format="png" size="sm" /> hero.png</div></td>
                        <td class="px-6 py-3">PNG → JPG</td>
                        <td class="px-6 py-3"><x-badge variant="success">Completed</x-badge></td>
                        <td class="px-6 py-3 text-[var(--ca-muted)]">2 min ago</td>
                    </tr>
                    <tr class="border-t" style="border-color:var(--ca-border);">
                        <td class="px-6 py-3"><div class="flex items-center gap-3"><x-file-icon format="pdf" size="sm" /> report.pdf</div></td>
                        <td class="px-6 py-3">PDF → JPG</td>
                        <td class="px-6 py-3"><x-badge variant="warning">Processing</x-badge></td>
                        <td class="px-6 py-3 text-[var(--ca-muted)]">just now</td>
                    </tr>
                </tbody>
            </table>
        </x-table.shell>

        <x-table.shell title="History" description="Empty state example">
            <x-table.empty-state
                title="No conversions yet"
                message="Upload a file to start converting."
                actionLabel="Upload file"
                actionUrl="#"
            />
        </x-table.shell>
    </div>

    {{-- Footer help cards --}}
    <h2 class="mt-12 mb-4 text-2xl font-semibold tracking-tight">Footer Help Cards</h2>
    <x-footer-help-cards />
</x-layouts.app>
