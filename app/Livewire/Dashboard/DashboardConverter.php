<?php

namespace App\Livewire\Dashboard;

use App\Actions\Conversions\CreateConversionJobAction;
use App\Actions\Conversions\EstimateConversionCostAction;
use App\Actions\Files\StoreUploadedFileAction;
use App\Enums\ConversionStatus;
use App\Exceptions\Billing\InsufficientCreditsException;
use App\Exceptions\Files\FileStorageException;
use App\Exceptions\Files\UnsupportedFileFormatException;
use App\Exceptions\Storage\StorageLimitExceededException;
use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Services\FeatureAccess\FeatureAccessService;
use App\Support\Conversions\Exceptions\UnsupportedConversionException;
use App\Support\Converters\ConverterRegistry;
use App\Support\Converters\DTO\ConverterTarget;
use App\Support\Converters\Exceptions\InvalidConverterOptionsException;
use App\Support\Converters\OptionsValidator;
use App\ViewModels\TargetFormatCardViewModel;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

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

    public int $pollCount = 0;

    public ?string $convertError = null;

    public ?int $estimatedCreditCost = null;

    public function updatedUpload(): void
    {
        $this->storeUpload(app(StoreUploadedFileAction::class), app(FeatureAccessService::class));
    }

    public function storeUpload(StoreUploadedFileAction $storeUploadedFile, FeatureAccessService $featureAccess): void
    {
        $this->resetErrorBag();
        $this->uploadError = null;

        $user = auth()->user();
        $maxMb = $featureAccess->limits($user)->maxFileSizeMb;

        $this->validate([
            'upload' => [
                'required',
                'file',
                'max:'.($maxMb * 1024),
            ],
        ], [
            'upload.max' => "This file is too large for your current plan. Max size: {$maxMb} MB.",
        ]);

        try {
            $fileRecord = $storeUploadedFile->handle(
                user: $user,
                file: $this->upload,
            );
        } catch (UnsupportedFileFormatException) {
            $this->uploadError = 'This file type is not supported in beta. Upload PNG, JPG, WEBP or PDF.';
            $this->step = 'upload';

            return;
        } catch (StorageLimitExceededException) {
            $this->uploadError = 'You have reached your storage limit. Delete some files to upload more.';
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

        try {
            $cost = app(EstimateConversionCostAction::class)->handle(
                $this->currentFile,
                $converter,
                $this->options,
            );
            $this->estimatedCreditCost = $cost->amount;
        } catch (\Throwable) {
            $this->estimatedCreditCost = null;
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

        $this->convertError = null;

        try {
            $job = app(CreateConversionJobAction::class)->handle(
                user: auth()->user(),
                sourceFile: $file,
                targetFormat: $this->selectedTargetFormat,
                options: $this->options,
            );
        } catch (UnsupportedConversionException $e) {
            $this->convertError = 'This conversion is not supported. Please choose a different format.';
            $this->step = 'format';

            return;
        } catch (InsufficientCreditsException $e) {
            $this->convertError = 'Not enough credits. This conversion requires '
                .($this->estimatedCreditCost ?? '?')
                .' credit(s). Please top up your balance.';

            return;
        } catch (Throwable) {
            $this->convertError = 'Something went wrong. Please try again.';

            return;
        }

        $this->currentConversionJobId = $job->id;
        $this->pollCount = 0;
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
        $this->estimatedCreditCost = null;
    }

    public function getCurrentJobProperty(): ?ConversionJob
    {
        if ($this->currentConversionJobId === null) {
            return null;
        }

        return ConversionJob::query()
            ->with(['sourceFile', 'resultFile'])
            ->where('user_id', auth()->id())
            ->find($this->currentConversionJobId);
    }

    public function convertAnother(): void
    {
        $this->reset(['upload', 'currentFileId', 'uploadError', 'selectedTargetFormat',
            'selectedConverterKey', 'targetFormatError', 'optionsSchema', 'options',
            'optionsByTarget', 'currentConversionJobId']);
        $this->step = 'upload';
    }

    public function convertWithDifferentSettings(): void
    {
        $this->currentConversionJobId = null;
        $this->pollCount = 0;

        if ($this->currentFile === null || $this->selectedTargetFormat === null) {
            $this->step = 'upload';

            return;
        }

        $this->step = 'settings';
    }

    public function refreshConversionStatus(): void
    {
        if ($this->currentConversionJobId === null) {
            return;
        }

        $this->pollCount++;

        if ($this->pollCount > 60) {
            $this->step = 'failed';
            $this->convertError = 'Conversion is taking too long. Please try again.';

            return;
        }

        $job = ConversionJob::query()
            ->where('user_id', auth()->id())
            ->find($this->currentConversionJobId);

        if (! $job) {
            return;
        }

        if ($job->status === ConversionStatus::Completed) {
            $this->step = 'completed';

            return;
        }

        if ($job->status === ConversionStatus::Failed) {
            $this->step = 'failed';
        }
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
