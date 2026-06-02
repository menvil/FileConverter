<?php

// CONV-197: Add Feature Access Integration Test

use App\Enums\Plan;
use App\Livewire\Dashboard\DashboardConverter;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('applies free plan limits across dashboard and upload flow', function () {
    Storage::fake('local');

    $user = User::factory()->create(['plan' => Plan::Free]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Free')
        ->assertSee('25 MB')
        ->assertSee('1 day');

    $validFile = UploadedFile::fake()->image('valid.png', 100, 100);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', $validFile)
        ->assertSet('step', 'format')
        ->assertHasNoErrors(['upload']);

    $tooLargeFile = UploadedFile::fake()->create('too-large.png', 26 * 1024, 'image/png');

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', $tooLargeFile)
        ->assertHasErrors(['upload']);
});

it('shows not included for api access on free plan', function () {
    $user = User::factory()->create(['plan' => Plan::Free]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Not included');
});

it('shows included for api access on pro plan', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Included');
});
