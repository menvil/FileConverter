<?php

declare(strict_types=1);

use App\Contracts\Billing\CreditLedger;
use App\Livewire\Dashboard\DashboardConverter;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('shows buy credits cta when conversion cannot start due to insufficient credits', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    // Drain all credits so user has 0
    $ledger = app(CreditLedger::class);
    $balance = $ledger->balance($user);
    if ($balance > 0) {
        $ledger->spend($user, $balance, 'test_drain');
    }

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', UploadedFile::fake()->image('test.png', 800, 600))
        ->call('selectTargetFormat', 'jpg')
        ->call('continueFromSettings')
        ->call('convert')
        ->assertSee('Not enough credits')
        ->assertSee('Buy credits');
});
