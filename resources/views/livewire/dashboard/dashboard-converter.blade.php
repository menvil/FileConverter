<div wire:init="ensureValidStep">
    <x-card variant="elevated">
        <x-stepper :steps="['File', 'Format', 'Settings', 'Convert']" :active="match ($step) {
            'format' => 'Format',
            'settings' => 'Settings',
            'convert' => 'Convert',
            default => 'File',
        }" class="mb-6" />
        @if ($step === 'upload' && $this->currentFile)
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
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <x-button variant="secondary" size="sm" wire:click="replaceFile">Replace</x-button>
                        <x-button variant="ghost" size="sm" wire:click="removeFile">Remove</x-button>
                    </div>
                </div>

                <div class="flex justify-end">
                    <x-button variant="primary" size="sm" wire:click="goToFormatStep">Choose format</x-button>
                </div>
            </div>
        @elseif ($step === 'upload')
            <div
                x-data="{ isDragging: false }"
                x-on:dragover.prevent="isDragging = true"
                x-on:dragenter.prevent="isDragging = true"
                x-on:dragleave.prevent="isDragging = false"
                x-on:drop="isDragging = false"
                x-bind:class="isDragging ? 'border-[var(--ca-primary)] bg-[var(--ca-primary)]/5' : 'border-[var(--ca-border)] bg-[var(--ca-surface-muted)]/40'"
                role="region"
                aria-label="File upload area"
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

                <label class="cursor-pointer" aria-label="Upload file">
                    <input type="file" wire:model="upload" wire:loading.attr="disabled" wire:target="upload,storeUpload" class="sr-only">
                    <span class="inline-flex items-center justify-center gap-2 rounded-[var(--ca-radius-md)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:brightness-110" style="background:var(--ca-primary);">
                        <span wire:loading.remove wire:target="upload,storeUpload">Choose file</span>
                        <span wire:loading wire:target="upload,storeUpload" aria-live="polite">Uploading…</span>
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
                <div>
                    <x-button variant="ghost" size="sm" wire:click="backToUploadSummary">← Back</x-button>
                </div>

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

                @if ($targetFormatError)
                    <div class="rounded-[var(--ca-radius-md)] border border-[var(--ca-danger)]/30 bg-[var(--ca-danger)]/5 px-4 py-3 text-sm text-[var(--ca-danger)]">
                        {{ $targetFormatError }}
                    </div>
                @endif

                <div wire:loading wire:target="selectTargetFormat" aria-live="polite" class="flex items-center gap-2 text-sm text-[var(--ca-muted)]">
                    <svg class="h-4 w-4 animate-spin" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                    </svg>
                    Loading converter settings…
                </div>

                @if (empty($this->targetFormatCards))
                    <div class="rounded-[var(--ca-radius-md)] border border-dashed border-[var(--ca-border)] bg-[var(--ca-surface-muted)]/40 px-6 py-8 text-center">
                        <p class="text-base font-semibold text-[var(--ca-text)]">No conversion targets available</p>
                        <p class="mt-1 text-sm text-[var(--ca-muted)]">We cannot convert this file type yet. Upload another file or check supported formats later.</p>
                        <div class="mt-4 flex justify-center">
                            <x-button variant="secondary" size="sm" wire:click="replaceFile">Upload another file</x-button>
                        </div>
                    </div>
                @else
                <div class="flex flex-col gap-3">
                    <p class="text-base font-semibold text-[var(--ca-text)]">Convert {{ strtoupper($file->extension) }} to</p>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($this->targetFormatCards as $card)
                            <button
                                type="button"
                                wire:click="selectTargetFormat('{{ $card->targetFormat }}')"
                                wire:loading.attr="disabled"
                                wire:target="selectTargetFormat"
                                class="flex items-start gap-3 rounded-[var(--ca-radius-md)] border border-[var(--ca-border)] bg-white p-4 text-left transition hover:border-[var(--ca-primary)] hover:bg-[var(--ca-primary)]/5 ca-focus-ring disabled:opacity-50 disabled:cursor-not-allowed">
                                <x-file-icon :format="$card->targetFormat" />
                                <span class="flex flex-col gap-0.5">
                                    <span class="flex items-center gap-2">
                                        <span class="text-sm font-semibold text-[var(--ca-text)]">{{ $card->label }}</span>
                                        @if ($card->recommended)
                                            <x-badge variant="purple" size="sm">Recommended</x-badge>
                                        @endif
                                    </span>
                                    <span class="text-xs text-[var(--ca-muted)]">{{ $card->description }}</span>
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        @endif

        @if ($step === 'settings' && $this->currentFile)
            @php($file = $this->currentFile)
            <div class="flex flex-col gap-4">
                <div>
                    <x-button variant="ghost" size="sm" wire:click="backToFormatStep">← Back</x-button>
                </div>

                <div class="flex flex-col gap-1">
                    <p class="text-base font-semibold text-[var(--ca-text)]">
                        Settings for {{ strtoupper($file->extension) }} to {{ strtoupper($selectedTargetFormat) }}
                    </p>
                    <p class="text-sm text-[var(--ca-muted)]">Adjust the conversion options below.</p>
                </div>

                @include('livewire.dashboard.dashboard-converter.partials.dynamic-options-form')

                <div
                    data-testid="estimated-cost"
                    class="flex items-center justify-between gap-4 rounded-[var(--ca-radius-md)] border border-[var(--ca-border)] bg-[var(--ca-surface-muted)]/40 px-4 py-3">
                    <span class="text-sm font-semibold text-[var(--ca-text)]">Estimated cost</span>
                    @if ($estimatedCreditCost !== null)
                        <p class="text-sm font-semibold text-[var(--ca-text)]">
                            {{ $estimatedCreditCost }} {{ $estimatedCreditCost === 1 ? 'credit' : 'credits' }}
                        </p>
                    @else
                        <p class="text-sm text-[var(--ca-muted)]">Credit cost will be calculated before conversion.</p>
                    @endif
                </div>

                <div class="flex justify-end">
                    <x-button variant="primary" size="sm" wire:click="continueFromSettings">Continue</x-button>
                </div>
            </div>
        @endif

        @if ($step === 'failed')
            <div class="flex flex-col gap-4">
                <div class="rounded-[var(--ca-radius-md)] border border-red-200 bg-red-50 px-6 py-8 text-center">
                    <p class="text-base font-semibold text-red-700">Conversion failed</p>
                    <p class="mt-1 text-sm text-red-600">We could not convert this file. Try another file or change settings.</p>
                </div>

                <div class="flex flex-col gap-2">
                    <x-button wire:click="convertWithDifferentSettings" class="w-full">
                        Change settings
                    </x-button>

                    <x-button variant="secondary" wire:click="convertAnother" class="w-full">
                        Try another file
                    </x-button>
                </div>
            </div>
        @endif

        @if ($step === 'completed' && $this->currentJob)
            @php($job = $this->currentJob)
            <div class="flex flex-col gap-4">
                @if ($job->resultFile?->isExpired())
                    <div class="rounded-[var(--ca-radius-md)] border border-[var(--ca-border)] bg-[var(--ca-surface-muted)]/40 px-6 py-8 text-center">
                        <p class="text-base font-semibold text-[var(--ca-text)]">This result has expired</p>
                        <p class="mt-1 text-sm text-[var(--ca-muted)]">Upload the original file again to create a new result.</p>
                    </div>

                    <x-button variant="ghost" wire:click="convertAnother" class="w-full">
                        Convert another file
                    </x-button>
                @else
                    <div class="rounded-[var(--ca-radius-md)] border border-[var(--ca-border)] bg-[var(--ca-surface-muted)]/40 px-6 py-8 text-center">
                        <p class="text-base font-semibold text-[var(--ca-text)]">Done! Your file is ready.</p>
                        @if ($job->resultFile)
                            <p class="mt-1 text-sm text-[var(--ca-muted)]">{{ $job->resultFile->original_name }}</p>
                        @endif
                    </div>

                    <div class="flex flex-col gap-2">
                        <a
                            href="{{ route('conversions.download', $job) }}"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-[var(--ca-radius-md)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:brightness-110"
                            style="background:var(--ca-primary);"
                        >Download</a>

                        <x-button variant="secondary" wire:click="convertWithDifferentSettings" class="w-full">
                            Change settings
                        </x-button>

                        <x-button variant="ghost" wire:click="convertAnother" class="w-full">
                            Convert another file
                        </x-button>
                    </div>
                @endif
            </div>
        @endif

        @if ($step === 'converting')
            <div wire:poll.2s="refreshConversionStatus">
                <div class="flex flex-col items-center gap-4 py-8 text-center">
                    <div class="h-12 w-12 animate-spin rounded-full border-4 border-[var(--ca-border)] border-t-[var(--ca-primary)]"></div>

                    <div>
                        <p class="text-base font-semibold text-[var(--ca-text)]">Converting your file</p>
                        @if ($this->currentJob)
                            <p class="mt-1 text-sm text-[var(--ca-muted)]">
                                {{ strtoupper($this->currentJob->source_format) }} → {{ strtoupper($this->currentJob->target_format) }}
                            </p>
                        @endif
                    </div>

                    <p class="text-xs text-[var(--ca-muted)]">Please keep this page open while we prepare your file.</p>
                </div>
            </div>
        @endif

        @if ($step === 'convert' && $this->currentFile)
            @php($file = $this->currentFile)
            <div class="flex flex-col gap-4">
                <div>
                    <x-button variant="ghost" size="sm" wire:click="goToSettingsStep">← Back</x-button>
                </div>

                @if ($convertError)
                    <div class="rounded-[var(--ca-radius-md)] border border-red-200 bg-red-50 px-4 py-3">
                        <p class="text-sm font-semibold text-red-700">Not enough credits</p>
                        <p class="mt-1 text-sm text-red-600">{{ $convertError }}</p>
                        @if ($hasInsufficientCredits)
                            <div class="mt-3">
                                <x-billing.buy-credits-cta variant="compact" />
                            </div>
                        @endif
                    </div>
                @endif

                <div class="rounded-[var(--ca-radius-md)] border border-dashed border-[var(--ca-border)] bg-[var(--ca-surface-muted)]/40 px-6 py-8 text-center">
                    <p class="text-base font-semibold text-[var(--ca-text)]">
                        Ready to convert {{ strtoupper($file->extension) }} to {{ strtoupper($selectedTargetFormat) }}
                    </p>
                </div>

                <x-button
                    wire:click="convert"
                    wire:loading.attr="disabled"
                    wire:target="convert"
                    :disabled="$step === 'converting'"
                    class="w-full"
                >
                    <span wire:loading.remove wire:target="convert">Convert Now</span>
                    <span wire:loading wire:target="convert" aria-live="polite">Starting conversion…</span>
                </x-button>
            </div>
        @endif
    </x-card>
</div>
