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

it('allows user to save default image quality preference', function () {
    $user = User::factory()->create([
        'settings' => [],
    ]);

    Livewire::actingAs($user)
        ->test(AccountSettingsForm::class)
        ->set('imageQuality', 'best')
        ->call('saveConversionPreferences')
        ->assertHasNoErrors();

    expect($user->fresh()->settings['conversion']['image_quality'])->toBe('best');
});

it('rejects invalid default image quality preference', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(AccountSettingsForm::class)
        ->set('imageQuality', 'ultra_fake')
        ->call('saveConversionPreferences')
        ->assertHasErrors(['imageQuality']);
});

it('allows user to save remove metadata preference', function () {
    $user = User::factory()->create([
        'settings' => [],
    ]);

    Livewire::actingAs($user)
        ->test(AccountSettingsForm::class)
        ->set('removeMetadata', false)
        ->call('saveConversionPreferences')
        ->assertHasNoErrors();

    expect($user->fresh()->settings['conversion']['remove_metadata'])->toBeFalse();
});

it('loads existing remove metadata preference on mount', function () {
    $user = User::factory()->create([
        'settings' => [
            'conversion' => [
                'remove_metadata' => false,
            ],
        ],
    ]);

    Livewire::actingAs($user)
        ->test(AccountSettingsForm::class)
        ->assertSet('removeMetadata', false);
});

it('shows success message after saving profile settings', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(AccountSettingsForm::class)
        ->set('name', 'Updated Name')
        ->call('saveProfile')
        ->assertSee('Profile settings saved');
});

it('shows success message after saving conversion preferences', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(AccountSettingsForm::class)
        ->set('imageQuality', 'high')
        ->set('removeMetadata', true)
        ->call('saveConversionPreferences')
        ->assertSee('Conversion preferences saved');
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
