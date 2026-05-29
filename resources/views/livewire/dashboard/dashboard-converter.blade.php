<div>
    <x-card variant="elevated">
        @if ($step === 'upload')
            <div
                x-data="{ isDragging: false }"
                x-on:dragover.prevent="isDragging = true"
                x-on:dragenter.prevent="isDragging = true"
                x-on:dragleave.prevent="isDragging = false"
                x-on:drop="isDragging = false"
                x-bind:class="isDragging ? 'border-[var(--ca-primary)] bg-[var(--ca-primary)]/5' : 'border-[var(--ca-border)] bg-[var(--ca-surface-muted)]/40'"
                class="flex flex-col items-center justify-center gap-4 rounded-[var(--ca-radius-md)] border-2 border-dashed px-6 py-12 text-center transition-colors">
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-[var(--ca-surface-muted)] text-[var(--ca-muted)]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 7.5m0 0L7.5 12M12 7.5V18" />
                    </svg>
                </div>

                <div class="flex flex-col gap-1">
                    <p class="text-base font-semibold text-[var(--ca-text)]">Drop your file here</p>
                    <p class="text-sm text-[var(--ca-muted)]">PNG, JPG, WEBP and PDF supported in beta</p>
                </div>

                <label class="cursor-pointer">
                    <input type="file" wire:model="upload" class="sr-only">
                    <span class="inline-flex items-center justify-center gap-2 rounded-[var(--ca-radius-md)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:brightness-110" style="background:var(--ca-primary);">
                        Choose file
                    </span>
                </label>

                <p class="text-xs text-[var(--ca-muted)]">Your files stay private. We delete them after conversion.</p>

                @error('upload')
                    <p class="text-sm text-[var(--ca-danger)]">{{ $message }}</p>
                @enderror

                @if ($uploadError)
                    <p class="text-sm text-[var(--ca-danger)]">{{ $uploadError }}</p>
                @endif
            </div>
        @endif

        @if ($step === 'format' && $this->currentFile)
            @php($file = $this->currentFile)
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between gap-4 rounded-[var(--ca-radius-md)] border border-[var(--ca-border)] bg-white p-4">
                    <div class="flex items-center gap-3">
                        <x-file-icon :format="$file->extension" />
                        <div class="flex flex-col">
                            <p class="text-sm font-semibold text-[var(--ca-text)]">{{ $file->original_name }}</p>
                            <p class="text-xs text-[var(--ca-muted)]">
                                {{ strtoupper($file->extension) }} ·
                                {{ number_format($file->size_bytes / 1024, 1) }} KB
                                @php($meta = $file->metadata_json ?? [])
                                @if (isset($meta['width'], $meta['height']))
                                    · {{ $meta['width'] }}×{{ $meta['height'] }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <x-button variant="secondary" size="sm" wire:click="replaceFile">Replace</x-button>
                        <x-button variant="ghost" size="sm" wire:click="removeFile">Remove</x-button>
                    </div>
                </div>

                <div class="rounded-[var(--ca-radius-md)] border border-dashed border-[var(--ca-border)] bg-[var(--ca-surface-muted)]/40 px-6 py-8 text-center">
                    <p class="text-base font-semibold text-[var(--ca-text)]">Choose output format</p>
                    <p class="mt-1 text-sm text-[var(--ca-muted)]">Target format selection will be added in Phase 7.</p>
                </div>
            </div>
        @endif
    </x-card>
</div>
