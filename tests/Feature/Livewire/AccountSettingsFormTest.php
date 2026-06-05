<?php

declare(strict_types=1);

use App\Livewire\AccountSettingsForm;
use App\Models\User;
use Livewire\Livewire;

it('renders account settings form component', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(AccountSettingsForm::class)
        ->assertSee('Account Settings');
});

it('shows current user profile data in settings form', function () {
    $user = User::factory()->create([
        'name' => 'Alex Johnson',
        'email' => 'alex@example.com',
    ]);

    Livewire::actingAs($user)
        ->test(AccountSettingsForm::class)
        ->assertSet('name', 'Alex Johnson')
        ->assertSee('alex@example.com');
});
