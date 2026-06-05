<?php

declare(strict_types=1);

use App\Livewire\ConversionHistoryTable;
use App\Models\User;
use Livewire\Livewire;

it('renders conversion history table component', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class)
        ->assertSee('History table');
});
