<?php

use App\Livewire\Dashboard\DashboardConverter;
use Livewire\Livewire;

it('renders dashboard converter component', function () {
    Livewire::test(DashboardConverter::class)
        ->assertSee('Drop your file here');
});
