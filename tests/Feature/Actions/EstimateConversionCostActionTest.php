<?php

declare(strict_types=1);

use App\Actions\Conversions\EstimateConversionCostAction;
use App\Models\FileRecord;
use App\Support\Converters\ConverterRegistry;

it('estimates conversion cost through application action', function () {
    $file = FileRecord::factory()->png()->create();
    $converter = app(ConverterRegistry::class)->find('png', 'jpg');

    $cost = app(EstimateConversionCostAction::class)->handle($file, $converter, []);

    expect($cost->amount)->toBe(1);
});

it('estimates pdf conversion cost through application action', function () {
    $file = FileRecord::factory()->png()->create();
    $converter = app(ConverterRegistry::class)->find('png', 'pdf');

    $cost = app(EstimateConversionCostAction::class)->handle($file, $converter, []);

    expect($cost->amount)->toBe(2);
});
