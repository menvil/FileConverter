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

it('allows user to update profile name', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
    ]);

    Livewire::actingAs($user)
        ->test(AccountSettingsForm::class)
        ->set('name', 'New Name')
        ->call('saveProfile')
        ->assertHasNoErrors();

    expect($user->fresh()->name)->toBe('New Name');
});

it('trims profile name before saving', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
    ]);

    Livewire::actingAs($user)
        ->test(AccountSettingsForm::class)
        ->set('name', '  New Name  ')
        ->call('saveProfile');

    expect($user->fresh()->name)->toBe('New Name');
});

it('requires profile name', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(AccountSettingsForm::class)
        ->set('name', '')
        ->call('saveProfile')
        ->assertHasErrors(['name' => 'required']);
});

it('displays email as read only on settings page', function () {
    $user = User::factory()->create([
        'email' => 'alex@example.com',
    ]);

    Livewire::actingAs($user)
        ->test(AccountSettingsForm::class)
        ->assertSee('alex@example.com')
        ->assertSee('Email');
});

it('does not update email from settings form', function () {
    $user = User::factory()->create([
        'email' => 'alex@example.com',
    ]);

    Livewire::actingAs($user)
        ->test(AccountSettingsForm::class)
        ->set('email', 'changed@example.com')
        ->call('saveProfile');

    expect($user->fresh()->email)->toBe('alex@example.com');
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
