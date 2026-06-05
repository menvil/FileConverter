<?php

declare(strict_types=1);

use App\Actions\Conversions\RecordConversionResultFileAction;
use App\Contracts\Billing\CreditLedger;
use App\Enums\ConversionStatus;
use App\Jobs\ProcessConversionJob;
use App\Livewire\Dashboard\DashboardConverter;
use App\Models\ConversionJob;
use App\Models\User;
use App\Support\Conversions\ConverterDriverRegistry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Fakes\Conversions\FakeConverterDriver;

it('completes the full mvp conversion happy path', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    // Free plan grants 50 starter credits via UserObserver.
    $balanceBefore = app(CreditLedger::class)->balance($user);
    expect($balanceBefore)->toBeGreaterThan(0);

    // Use a fake driver so the test is stable regardless of Imagick availability.
    app()->instance(
        ConverterDriverRegistry::class,
        new ConverterDriverRegistry([
            new FakeConverterDriver('png_to_jpg'),
        ]),
    );

    $component = Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', UploadedFile::fake()->image('sample.png', 100, 100))
        ->assertSet('step', 'format')
        ->assertSee('JPG')
        ->call('selectTargetFormat', 'jpg')
        ->assertSet('step', 'settings')
        ->set('options.quality', 'high')
        ->call('continueFromSettings')
        ->assertSet('step', 'convert')
        ->call('convert')
        ->assertSet('step', 'converting');

    $job = ConversionJob::query()->latest()->firstOrFail();

    expect($job->source_format)->toBe('png');
    expect($job->target_format)->toBe('jpg');
    expect($job->user_id)->toBe($user->id);

    // If queue is sync, job already ran. If not, run it now.
    if ($job->status !== ConversionStatus::Completed) {
        (new ProcessConversionJob($job->id))->handle(
            app(ConverterDriverRegistry::class),
            app(RecordConversionResultFileAction::class),
            app(CreditLedger::class),
        );
    }

    $component
        ->call('refreshConversionStatus')
        ->assertSet('step', 'completed')
        ->assertSee('Download');

    $job->refresh();

    expect($job->status)->toBe(ConversionStatus::Completed);
    expect($job->result_file_id)->not->toBeNull();

    // Credits were spent on success.
    $balanceAfter = app(CreditLedger::class)->balance($user);
    expect($balanceAfter)->toBeLessThan($balanceBefore);

    // History row exists.
    $this->assertDatabaseHas('conversion_jobs', [
        'id' => $job->id,
        'user_id' => $user->id,
        'status' => ConversionStatus::Completed->value,
        'source_format' => 'png',
        'target_format' => 'jpg',
    ]);

    // Result is downloadable.
    $this->actingAs($user)
        ->get(route('conversions.download', $job))
        ->assertOk();
});
