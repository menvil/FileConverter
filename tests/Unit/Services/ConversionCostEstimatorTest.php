<?php

declare(strict_types=1);

use App\Contracts\Billing\ConversionCostEstimator;
use App\Models\FileRecord;
use App\Support\Converters\ConverterRegistry;

it('estimates image to image conversion as one credit', function () {
    $file = FileRecord::factory()->png()->make(['user_id' => 1]);
    $converter = app(ConverterRegistry::class)->find('png', 'jpg');

    $cost = app(ConversionCostEstimator::class)->estimate($file, $converter, []);

    expect($cost->amount)->toBe(1);
});

it('estimates jpg to webp conversion as one credit', function () {
    $file = FileRecord::factory()->jpg()->make(['user_id' => 1]);
    $converter = app(ConverterRegistry::class)->find('jpg', 'webp');

    $cost = app(ConversionCostEstimator::class)->estimate($file, $converter, []);

    expect($cost->amount)->toBe(1);
});

it('estimates image to pdf conversion as two credits', function () {
    $file = FileRecord::factory()->png()->make(['user_id' => 1]);
    $converter = app(ConverterRegistry::class)->find('png', 'pdf');

    $cost = app(ConversionCostEstimator::class)->estimate($file, $converter, []);

    expect($cost->amount)->toBe(2);
});

it('estimates jpg to pdf conversion as two credits', function () {
    $file = FileRecord::factory()->jpg()->make(['user_id' => 1]);
    $converter = app(ConverterRegistry::class)->find('jpg', 'pdf');

    $cost = app(ConversionCostEstimator::class)->estimate($file, $converter, []);

    expect($cost->amount)->toBe(2);
});
