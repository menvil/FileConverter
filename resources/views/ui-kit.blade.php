<x-layouts.app title="UI Kit — ConvertAI">
    <header class="flex flex-col gap-2">
        <span class="text-sm uppercase tracking-wider text-[var(--ca-muted)]">Internal</span>
        <h1 class="text-3xl font-semibold tracking-tight">UI Kit</h1>
        <p class="max-w-2xl text-[var(--ca-muted)]">All UI primitives — design tokens, buttons, cards, badges, icons, stepper, form controls, conversion states, and table shells. Source: <code>resources/views/components/</code>.</p>
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
        <div class="flex flex-col gap-8">
            <x-stepper :steps="['File', 'Format', 'Settings', 'Convert']" active="File" />
            <x-stepper :steps="['File', 'Settings', 'Convert']" active="Settings" />
            <x-stepper :steps="['File', 'Settings', 'Convert']" active="Convert" />
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

    {{-- Conversion State Explorer (interactive) --}}
    <h2 class="mt-12 mb-4 text-2xl font-semibold tracking-tight">Conversion · State explorer</h2>
    <x-card variant="elevated">
        <x-conversion.explorer />
    </x-card>

    {{-- Conversion: empty upload --}}
    <h2 class="mt-12 mb-4 text-2xl font-semibold tracking-tight">Conversion · Empty upload (simple)</h2>
    <x-card variant="elevated">
        <x-conversion.dropzone variant="simple" />
    </x-card>

    <h2 class="mt-8 mb-4 text-2xl font-semibold tracking-tight">Conversion · Empty upload (illustrated)</h2>
    <x-card variant="elevated">
        <x-conversion.dropzone variant="illustrated" />
    </x-card>

    {{-- Conversion: file chip --}}
    <h2 class="mt-12 mb-4 text-2xl font-semibold tracking-tight">Conversion · Uploaded file chip</h2>
    <x-card variant="elevated">
        <x-conversion.file-chip name="Part5.mov" format="webp" size="99.3 MB" />
    </x-card>

    {{-- Conversion: Settings step (image) --}}
    <h2 class="mt-12 mb-4 text-2xl font-semibold tracking-tight">Conversion · Settings step (image)</h2>
    <x-card variant="elevated">
        <div class="flex flex-col gap-6">
            <x-stepper :steps="['File', 'Settings', 'Convert']" active="Settings" />
            <x-conversion.file-chip name="upload_form2.jpg" format="jpg" size="23 KB" />

            <div class="grid gap-6 md:grid-cols-2">
                <x-conversion.format-select name="target" value="png" />
                <x-conversion.segmented
                    name="resize"
                    label="Resize"
                    :options="['original' => 'Original', '1920' => 'Up to 1920px', '1280' => 'Up to 1280px']"
                    value="original"
                />
            </div>

            <x-conversion.option-row
                name="remove_bg"
                label="Remove background"
                description="AI cuts out the subject"
                :ai="true"
            />

            <x-conversion.convert-button to="png" estSize="20 KB" :showBack="true" />
        </div>
    </x-card>

    {{-- Conversion: Settings step (video) --}}
    <h2 class="mt-8 mb-4 text-2xl font-semibold tracking-tight">Conversion · Settings step (video)</h2>
    <x-card variant="elevated">
        <div class="flex flex-col gap-5">
            <x-conversion.file-chip name="Part5.mov" format="webp" size="99.3 MB" />
            <x-conversion.format-select name="vtarget" value="pdf" :options="['mp3' => 'MP3', 'mp4' => 'MP4', 'webp' => 'WEBP', 'pdf' => 'PDF']" />
            <x-conversion.segmented
                name="resolution"
                label="Resolution"
                :options="['480' => '480p', '720' => '720p', '1080' => '1080p', '4k' => '4K']"
                value="1080"
            />
            <x-conversion.option-row name="audio_only" label="Audio only" description="Extract the audio track" />
            <x-conversion.option-row name="subtitles" label="Subtitles" description="Save as a separate SRT" :ai="true" />

            <div class="flex items-center justify-between gap-3 pt-2">
                <span class="text-sm text-[var(--ca-muted)]">~ est. size: <span class="font-semibold text-[var(--ca-text)]">99.3 MB</span></span>
                <button type="button" class="ca-gradient-primary inline-flex items-center gap-2 rounded-full px-6 py-3 text-base font-semibold">
                    <span aria-hidden="true">→</span> MP3 <span aria-hidden="true">→</span>
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-2 pt-3 text-sm text-[var(--ca-muted)]">
                <span>Try:</span>
                <x-file-icon format="pdf" size="sm" />
                <x-file-icon format="jpg" size="sm" />
                <x-file-icon format="png" size="sm" />
                <x-file-icon format="webp" size="sm" />
            </div>
        </div>
    </x-card>

    {{-- Conversion: progress --}}
    <h2 class="mt-12 mb-4 text-2xl font-semibold tracking-tight">Conversion · In progress</h2>
    <x-conversion.progress name="upload_form.jpg" format="jpg" from="jpg" to="png" :percent="96" />

    {{-- Conversion: done --}}
    <h2 class="mt-8 mb-4 text-2xl font-semibold tracking-tight">Conversion · Done</h2>
    <x-conversion.done name="Part5.mov" to="pdf" />

    {{-- User dropdown --}}
    <h2 class="mt-12 mb-4 text-2xl font-semibold tracking-tight">User Dropdown</h2>
    <x-card variant="elevated">
        <p class="mb-4 text-sm text-[var(--ca-muted)]">Click the user pill in the header to expand. Alpine-powered.</p>
        <div class="inline-block">
            <x-user-dropdown />
        </div>
    </x-card>

    {{-- Table tabs --}}
    <h2 class="mt-12 mb-4 text-2xl font-semibold tracking-tight">Table Tabs</h2>
    <x-card variant="elevated">
        <x-table.tabs :tabs="[
            ['label' => 'All', 'count' => 18],
            ['label' => 'Completed', 'count' => 16],
            ['label' => 'Processing', 'count' => 1],
            ['label' => 'Starred', 'count' => 3],
        ]" active="All" />
    </x-card>

    {{-- Recent Conversions --}}
    <h2 class="mt-12 mb-4 text-2xl font-semibold tracking-tight">Recent Conversions Table</h2>
    <x-table.recent-conversions
        :rows="[
            ['name' => 'Marketing Report.pdf', 'from' => 'pdf', 'to' => 'docx', 'size' => '24.8 MB', 'date' => 'May 12, 10:24 AM', 'status' => 'completed'],
            ['name' => 'Project Proposal.docx', 'from' => 'jpg', 'to' => 'pdf', 'size' => '1.8 MB', 'date' => 'May 12, 09:58 AM', 'status' => 'completed', 'starred' => true],
            ['name' => 'Sales Data.xlsx', 'from' => 'png', 'to' => 'webp', 'size' => '612 KB', 'date' => 'May 12, 09:15 AM', 'status' => 'completed'],
            ['name' => 'Contract.doc', 'from' => 'pdf', 'to' => 'pdf', 'size' => '412 KB', 'date' => 'May 11, 11:07 AM', 'status' => 'processing'],
        ]"
    />

    {{-- Empty state --}}
    <h2 class="mt-12 mb-4 text-2xl font-semibold tracking-tight">Table Shell · Empty state</h2>
    <x-table.shell title="History" description="Empty state example">
        <x-table.empty-state
            title="No conversions yet"
            message="Upload a file to start converting."
            actionLabel="Upload file"
            actionUrl="#"
        />
    </x-table.shell>

    {{-- Footer help cards --}}
    <h2 class="mt-12 mb-4 text-2xl font-semibold tracking-tight">Footer Help Cards</h2>
    <x-footer-help-cards />
</x-layouts.app>
