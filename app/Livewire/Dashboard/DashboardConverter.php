<?php

namespace App\Livewire\Dashboard;

use App\Actions\Files\StoreUploadedFileAction;
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
            'upload' => UploadedFileRules::rules(),
        ]);

        $fileRecord = $storeUploadedFile->handle(
            user: auth()->user(),
            file: $this->upload,
        );

        $this->currentFileId = $fileRecord->id;
        $this->step = 'format';
    }

    public function replaceFile(): void
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
