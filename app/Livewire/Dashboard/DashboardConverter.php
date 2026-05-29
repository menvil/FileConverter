<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;

class DashboardConverter extends Component
{
    public string $step = 'upload';

    public $upload = null;

    public ?int $currentFileId = null;

    public ?string $uploadError = null;

    public function render()
    {
        return view('livewire.dashboard.dashboard-converter');
    }
}
