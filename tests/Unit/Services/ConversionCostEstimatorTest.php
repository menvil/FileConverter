<?php

declare(strict_types=1);

use App\Contracts\Billing\ConversionCostEstimator;
use App\Exceptions\Billing\UnsupportedConversionCostException;
use App\Models\FileRecord;
use App\Support\Converters\ConverterRegistry;
use Tests\Fakes\Converters\FakeUnsupportedConverter;

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

it('estimates png to webp conversion as one credit', function () {
    $file = FileRecord::factory()->png()->make(['user_id' => 1]);
    $converter = app(ConverterRegistry::class)->find('png', 'webp');

    $cost = app(ConversionCostEstimator::class)->estimate($file, $converter, []);

    expect($cost->amount)->toBe(1);
});

it('rejects unsupported cost estimation', function () {
    $file = FileRecord::factory()->png()->make(['user_id' => 1]);
    $converter = new FakeUnsupportedConverter('png', 'mp3');

    app(ConversionCostEstimator::class)->estimate($file, $converter, []);
})->throws(UnsupportedConversionCostException::class);

it('returns stable cost breakdown', function () {
    $file = FileRecord::factory()->png()->make(['user_id' => 1]);
    $converter = app(ConverterRegistry::class)->find('png', 'pdf');

    $cost = app(ConversionCostEstimator::class)->estimate($file, $converter, []);

    expect($cost->breakdown)->toHaveKeys([
        'base',
        'size',
        'features',
        'total',
        'details',
    ]);

    expect($cost->breakdown['total'])->toBe($cost->amount);
});
