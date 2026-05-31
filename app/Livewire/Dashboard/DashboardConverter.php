<?php

namespace App\Livewire\Dashboard;

use App\Actions\Files\StoreUploadedFileAction;
use App\Exceptions\Files\FileStorageException;
use App\Exceptions\Files\UnsupportedFileFormatException;
use App\Models\FileRecord;
use App\Support\Files\UploadedFileRules;
use Livewire\Component;
use Livewire\WithFileUploads;

class DashboardConverter extends Component
{
    use WithFileUploads;

    public string $step = 'upload';

    public $upload = null;

    public ?int $currentFileId = null;

    public ?string $uploadError = null;

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
        $this->resetErrorBag();
        $this->step = 'upload';
    }

    public function getCurrentFileProperty(): ?FileRecord
    {
        return $this->currentFileId
            ? FileRecord::query()->where('user_id', auth()->id())->find($this->currentFileId)
            : null;
    }

    public function render()
    {
        return view('livewire.dashboard.dashboard-converter');
    }
}
