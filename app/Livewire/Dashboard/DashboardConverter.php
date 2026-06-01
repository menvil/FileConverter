<?php

namespace App\Livewire\Dashboard;

use App\Actions\Conversions\CreateConversionJobAction;
use App\Actions\Files\StoreUploadedFileAction;
use App\Exceptions\Files\FileStorageException;
use App\Exceptions\Files\UnsupportedFileFormatException;
use App\Models\FileRecord;
use App\Support\Converters\ConverterRegistry;
use App\Support\Converters\DTO\ConverterTarget;
use App\Support\Converters\Exceptions\InvalidConverterOptionsException;
use App\Support\Converters\OptionsValidator;
use App\Support\Files\UploadedFileRules;
use App\ViewModels\TargetFormatCardViewModel;
use Livewire\Component;
use Livewire\WithFileUploads;

class DashboardConverter extends Component
{
    use WithFileUploads;

    public string $step = 'upload';

    public $upload = null;

    public ?int $currentFileId = null;

    public ?string $uploadError = null;

    public ?string $selectedTargetFormat = null;

    public ?string $selectedConverterKey = null;

    public ?string $targetFormatError = null;

    /** @var array<int, array<string, mixed>> */
    public array $optionsSchema = [];

    /** @var array<string, mixed> */
    public array $options = [];

    /**
     * Per-target cache of entered option values so navigating settings → format
     * → settings restores the user's input for the same target. Component-local
     * only — never persisted to the database in Phase 8.
     *
     * @var array<string, array<string, mixed>>
     */
    public array $optionsByTarget = [];

    public ?int $currentConversionJobId = null;

    public function updatedUpload(): void
    {
        $this->storeUpload(app(StoreUploadedFileAction::class));
    }

    public function storeUpload(StoreUploadedFileAction $storeUploadedFile): void
    {
        $this->resetErrorBag();
        $this->uploadError = null;

        $this->validate([
            'upload' => [
                'required',
                'file',
                'max:'.UploadedFileRules::MAX_FILE_KILOBYTES,
            ],
        ], [
            'upload.max' => 'This file is too large. Max upload size is '.(UploadedFileRules::MAX_FILE_KILOBYTES / 1024).' MB.',
        ]);

        try {
            $fileRecord = $storeUploadedFile->handle(
                user: auth()->user(),
                file: $this->upload,
            );
        } catch (UnsupportedFileFormatException) {
            $this->uploadError = 'This file type is not supported in beta. Upload PNG, JPG, WEBP or PDF.';
            $this->step = 'upload';

            return;
        } catch (FileStorageException) {
            $this->uploadError = 'We could not store your file. Please try again.';
            $this->step = 'upload';

            return;
        }

        $this->currentFileId = $fileRecord->id;
        $this->step = 'format';
    }

    public function goToFormatStep(): void
    {
        if ($this->currentFile === null) {
            $this->currentFileId = null;
            $this->step = 'upload';

            return;
        }

        $this->rememberCurrentOptions();
        $this->step = 'format';
    }

    public function ensureValidStep(): void
    {
        if (in_array($this->step, ['format', 'settings'], true) && $this->currentFile === null) {
            $this->currentFileId = null;
            $this->step = 'upload';

            return;
        }

        if ($this->step === 'settings' && $this->selectedTargetFormat === null) {
            $this->step = 'format';
        }
    }

    public function goToSettingsStep(): void
    {
        if ($this->currentFile === null) {
            $this->resetTargetSelection();
            $this->currentFileId = null;
            $this->step = 'upload';

            return;
        }

        if ($this->selectedTargetFormat === null) {
            $this->step = 'format';

            return;
        }

        $this->step = 'settings';
    }

    public function selectTargetFormat(string $targetFormat): void
    {
        $this->targetFormatError = null;

        if ($this->currentFile === null) {
            $this->currentFileId = null;
            $this->selectedTargetFormat = null;
            $this->step = 'upload';

            return;
        }

        $converter = app(ConverterRegistry::class)->find(
            $this->currentFile->extension,
            $targetFormat,
        );

        if ($converter === null) {
            $this->resetTargetSelection();
            $this->targetFormatError = 'This conversion is not supported yet.';
            $this->step = 'format';

            return;
        }

        $this->selectedTargetFormat = $converter->targetFormat();
        $this->selectedConverterKey = $converter->key();
        $this->optionsSchema = $converter->optionsSchema();

        if (array_key_exists($this->selectedTargetFormat, $this->optionsByTarget)) {
            $this->options = $this->optionsByTarget[$this->selectedTargetFormat];
        } else {
            $this->initializeOptionsFromSchema();
        }

        $this->step = 'settings';
    }

    private function rememberCurrentOptions(): void
    {
        if ($this->selectedTargetFormat === null) {
            return;
        }

        $this->optionsByTarget[$this->selectedTargetFormat] = $this->options;
    }

    public function continueFromSettings(): void
    {
        if ($this->currentFile === null || $this->selectedTargetFormat === null) {
            $this->goToSettingsStep();

            return;
        }

        if (! $this->validateSettings()) {
            $this->step = 'settings';

            return;
        }

        $this->step = 'convert';
    }

    public function convert(): void
    {
        if ($this->step === 'converting' || $this->currentConversionJobId !== null) {
            return;
        }

        $file = $this->currentFile;

        if ($file === null || $this->selectedTargetFormat === null) {
            return;
        }

        $job = app(CreateConversionJobAction::class)->handle(
            user: auth()->user(),
            sourceFile: $file,
            targetFormat: $this->selectedTargetFormat,
            options: $this->options,
        );

        $this->currentConversionJobId = $job->id;
        $this->step = 'converting';
    }

    public function validateSettings(): bool
    {
        $this->resetErrorBag();

        try {
            app(OptionsValidator::class)->validate($this->optionsSchema, $this->options);
        } catch (InvalidConverterOptionsException $exception) {
            foreach ($exception->fieldErrors() as $field => $message) {
                $this->addError("options.{$field}", $message);
            }

            return false;
        }

        return true;
    }

    private function initializeOptionsFromSchema(): void
    {
        $this->options = [];

        foreach ($this->optionsSchema as $field) {
            if (! isset($field['key']) || ! array_key_exists('default', $field)) {
                continue;
            }

            $this->options[$field['key']] = $field['default'];
        }
    }

    public function backToUploadSummary(): void
    {
        if ($this->currentFile === null) {
            $this->currentFileId = null;
            $this->step = 'upload';

            return;
        }

        $this->resetTargetSelection();
        $this->step = 'upload';
    }

    public function backToFormatStep(): void
    {
        if ($this->currentFile === null) {
            $this->currentFileId = null;
            $this->step = 'upload';

            return;
        }

        $this->rememberCurrentOptions();
        $this->step = 'format';
    }

    public function replaceFile(): void
    {
        $this->resetCurrentUpload();
    }

    public function removeFile(): void
    {
        $this->resetCurrentUpload();
    }

    private function resetCurrentUpload(): void
    {
        $this->reset('upload', 'currentFileId', 'uploadError');
        $this->resetTargetSelection();
        $this->resetErrorBag();
        $this->step = 'upload';
    }

    private function resetTargetSelection(): void
    {
        $this->selectedTargetFormat = null;
        $this->selectedConverterKey = null;
        $this->targetFormatError = null;
        $this->optionsSchema = [];
        $this->options = [];
        $this->optionsByTarget = [];
    }

    public function getCurrentFileProperty(): ?FileRecord
    {
        return $this->currentFileId
            ? FileRecord::query()->where('user_id', auth()->id())->find($this->currentFileId)
            : null;
    }

    /** @return list<ConverterTarget> */
    public function getAvailableTargetsProperty(): array
    {
        if (! $this->currentFile) {
            return [];
        }

        return app(ConverterRegistry::class)->targetsFor($this->currentFile->extension);
    }

    /** @return list<TargetFormatCardViewModel> */
    public function getTargetFormatCardsProperty(): array
    {
        return array_map(
            fn (ConverterTarget $target): TargetFormatCardViewModel => TargetFormatCardViewModel::fromTarget($target),
            $this->availableTargets,
        );
    }

    public function render()
    {
        return view('livewire.dashboard.dashboard-converter');
    }
}
