{{-- Interactive state explorer for the conversion form. --}}
@php
    $steps = [
        ['label' => 'File'],
        ['label' => 'Settings'],
        ['label' => 'Convert'],
        ['label' => 'Done'],
    ];
@endphp

<div
    x-data="{
        step: 0,
        percent: 0,
        timer: null,
        goTo(s) {
            this.step = s;
            clearInterval(this.timer);
            this.percent = 0;
            if (s === 2) {
                this.timer = setInterval(() => {
                    if (this.percent >= 100) {
                        clearInterval(this.timer);
                        this.step = 3;
                        return;
                    }
                    this.percent = Math.min(100, this.percent + 7);
                }, 200);
            }
        }
    }"
    class="flex flex-col gap-6"
>
    {{-- Step controls --}}
    <div class="flex flex-col gap-3">
        <ol class="flex w-full flex-wrap items-center gap-3">
            @foreach ($steps as $i => $s)
                <li class="flex flex-1 items-center gap-3 min-w-[7rem]">
                    <button
                        type="button"
                        @click="goTo({{ $i }})"
                        :class="step === {{ $i }}
                            ? 'ca-gradient-primary text-white shadow-sm border-transparent'
                            : step > {{ $i }}
                                ? 'border bg-white text-[var(--ca-text)]'
                                : 'border bg-white text-[var(--ca-muted)]'"
                        class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-semibold ca-focus-ring transition"
                        style="border-color:var(--ca-border);"
                    >
                        <template x-if="step > {{ $i }}">
                            <span aria-hidden="true" class="inline-flex h-5 w-5 items-center justify-center rounded-full" style="background:var(--ca-success);color:white;">
                                <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7 7a1 1 0 0 1-1.4 0l-3.3-3.3a1 1 0 1 1 1.4-1.4L9 11.6l6.3-6.3a1 1 0 0 1 1.4 0z" clip-rule="evenodd"/></svg>
                            </span>
                        </template>
                        <template x-if="step <= {{ $i }}">
                            <span aria-hidden="true"
                                :class="step === {{ $i }} ? 'bg-white/25 text-white' : 'bg-[var(--ca-surface-muted)] text-[var(--ca-muted)]'"
                                class="inline-flex h-5 w-5 items-center justify-center rounded-full text-xs font-semibold"
                            >{{ $i + 1 }}</span>
                        </template>
                        <span>{{ $s['label'] }}</span>
                    </button>
                    @if (! $loop->last)
                        <span class="hidden h-px flex-1 bg-[var(--ca-border)] md:block" aria-hidden="true"></span>
                    @endif
                </li>
            @endforeach
        </ol>
        <p class="text-xs text-[var(--ca-muted)]">Demo · click any step to preview the form state.</p>
    </div>

    {{-- Step 0 — empty upload --}}
    <div x-show="step === 0" x-cloak class="flex flex-col gap-4">
        <x-conversion.dropzone variant="illustrated" />
        <div class="flex justify-end">
            <button
                type="button"
                @click="goTo(1)"
                class="ca-gradient-primary inline-flex items-center justify-center gap-2 rounded-[var(--ca-radius-lg)] px-5 py-2.5 text-sm font-semibold ca-focus-ring"
            >Pretend a file was selected →</button>
        </div>
    </div>

    {{-- Step 1 — Settings --}}
    <div x-show="step === 1" x-cloak class="flex flex-col gap-5">
        <x-conversion.file-chip name="upload_form.jpg" format="jpg" size="20 KB" />

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

        <div class="flex flex-wrap items-center justify-between gap-3">
            <button type="button" @click="goTo(0)" class="inline-flex items-center gap-2 text-sm font-medium text-[var(--ca-text)] ca-focus-ring">
                <span aria-hidden="true">←</span> Back
            </button>
            <div class="flex items-center gap-4">
                <span class="text-sm text-[var(--ca-muted)]">~ est. size: <span class="font-semibold text-[var(--ca-text)]">20 KB</span></span>
                <button
                    type="button"
                    @click="goTo(2)"
                    class="ca-gradient-primary inline-flex items-center justify-center gap-2 rounded-[var(--ca-radius-lg)] px-6 py-3 text-base font-semibold shadow-sm ca-focus-ring"
                >
                    <span>Convert Now</span>
                    <span aria-hidden="true">→</span>
                    <span>PNG</span>
                    <span aria-hidden="true">→</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Step 2 — Converting --}}
    <div x-show="step === 2" x-cloak class="flex flex-col gap-4">
        <div class="rounded-[var(--ca-radius-lg)] border bg-white px-5 py-4 shadow-[var(--ca-shadow-card)]" style="border-color:var(--ca-border);">
            <div class="flex items-center gap-3">
                <x-file-icon format="jpg" size="md" />
                <div class="flex min-w-0 flex-1 flex-col">
                    <span class="truncate text-base font-semibold text-[var(--ca-text)]">upload_form.jpg</span>
                    <span class="text-sm text-[var(--ca-muted)]">
                        <span>JPG</span>
                        <span aria-hidden="true">→</span>
                        <span class="font-semibold text-[var(--ca-text)]">PNG</span>
                    </span>
                </div>
                <span class="text-lg font-bold text-[var(--ca-text)]" x-text="percent + '%'">0%</span>
            </div>
            <div
                role="progressbar"
                aria-valuemin="0"
                aria-valuemax="100"
                :aria-valuenow="percent"
                class="mt-3 h-1.5 w-full overflow-hidden rounded-full"
                style="background:var(--ca-surface-muted);"
            >
                <div class="ca-gradient-primary h-full rounded-full transition-[width] duration-200" :style="'width:' + percent + '%'"></div>
            </div>
        </div>
        <div class="flex justify-between">
            <button type="button" @click="goTo(1)" class="inline-flex items-center gap-2 text-sm font-medium text-[var(--ca-text)] ca-focus-ring">
                <span aria-hidden="true">←</span> Cancel
            </button>
            <button type="button" @click="goTo(3)" class="text-sm font-medium text-[var(--ca-primary)] ca-focus-ring">Skip to Done →</button>
        </div>
    </div>

    {{-- Step 3 — Done --}}
    <div x-show="step === 3" x-cloak class="flex flex-col gap-4">
        <x-conversion.done name="upload_form.jpg" to="png" />
        <div class="flex justify-end">
            <button type="button" @click="goTo(0)" class="text-sm font-medium text-[var(--ca-primary)] ca-focus-ring">Restart demo →</button>
        </div>
    </div>
</div>
