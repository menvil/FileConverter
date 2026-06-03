<?php

declare(strict_types=1);

use App\Livewire\Billing\BillingPage;
use App\Models\User;
use Livewire\Livewire;

it('renders billing page livewire component', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('Billing');
});
