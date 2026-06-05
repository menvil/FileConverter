<?php

use App\Livewire\Dashboard\DashboardConverter;
use App\Models\User;
use Livewire\Livewire;

it('renders main MVP pages after UX polish', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/dashboard')->assertOk();
    $this->actingAs($user)->get('/history')->assertOk();
    $this->actingAs($user)->get('/billing')->assertOk();
    $this->actingAs($user)->get('/settings')->assertOk();
});

it('renders dashboard converter with polish UI elements', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(DashboardConverter::class)
        ->assertSee('Drop your file here')
        ->assertSee('File upload area', false)
        ->assertSee('Choose file')
        ->assertSee('Uploading');
});

it('renders toast region in app layout', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('toast-region', false);
});

it('renders upload loading state hooks in converter', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(DashboardConverter::class)
        ->assertSeeHtml('wire:loading')
        ->assertSeeHtml('wire:target="upload,storeUpload"');
});

it('renders mobile navigation toggle in header', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertSeeHtml('mobile-nav')
        ->assertSeeHtml('Toggle navigation menu');
});

it('renders accessible labels in recent conversions table filters', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertSeeHtml('for="recent-search"')
        ->assertSeeHtml('for="recent-status"');
});

it('renders billing and settings navigation links in user dropdown', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Billing')
        ->assertSee('Settings');
});

it('renders helpful empty states on all main pages for a fresh user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('No conversions yet');

    $this->actingAs($user)
        ->get('/history')
        ->assertOk()
        ->assertSee('No conversion history yet');
});
