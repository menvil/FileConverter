<?php

use App\Livewire\Dashboard\DashboardConverter;
use Livewire\Livewire;

it('renders empty upload state', function () {
    Livewire::test(DashboardConverter::class)
        ->assertSee('Drop your file here')
        ->assertSee('PNG, JPG, WEBP and PDF supported in beta')
        ->assertSee('Choose file');
});
