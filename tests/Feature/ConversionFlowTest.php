<?php

declare(strict_types=1);

use App\Actions\Conversions\RecordConversionResultFileAction;
use App\Contracts\Billing\CreditLedger;
use App\Jobs\ProcessConversionJob;
use App\Livewire\Dashboard\DashboardConverter;
use App\Models\ConversionJob;
use App\Models\User;
use App\Support\Conversions\ConverterDriverRegistry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('allows user to upload configure convert and download image result', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', UploadedFile::fake()->image('avatar.png', 800, 600))
        ->assertSet('step', 'format')
        ->call('selectTargetFormat', 'jpg')
        ->assertSet('step', 'settings')
        ->set('options.quality', 'high')
        ->call('continueFromSettings')
        ->assertSet('step', 'convert')
        ->call('convert')
        ->assertSet('step', 'converting');

    $job = ConversionJob::query()->firstOrFail();

    (new ProcessConversionJob($job->id))->handle(
        app(ConverterDriverRegistry::class),
        app(RecordConversionResultFileAction::class),
        app(CreditLedger::class),
    );

    $component
        ->call('refreshConversionStatus')
        ->assertSet('step', 'completed')
        ->assertSee('Download');

    $this->actingAs($user)
        ->get(route('conversions.download', $job->fresh()))
        ->assertOk();
});
