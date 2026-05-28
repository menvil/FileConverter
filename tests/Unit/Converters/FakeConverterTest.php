<?php

use App\Support\Converters\Contracts\Converter;
use Tests\Fakes\Converters\FakeJpgToWebpConverter;
use Tests\Fakes\Converters\FakePngToJpgConverter;
use Tests\Fakes\Converters\FakePngToPdfConverter;

it('has fake png to jpg converter', function () {
    $converter = new FakePngToJpgConverter;

    expect($converter)->toBeInstanceOf(Converter::class);
    expect($converter->key())->toBe('png_to_jpg');
    expect($converter->sourceFormat())->toBe('png');
    expect($converter->targetFormat())->toBe('jpg');
});

it('has fake png to pdf converter', function () {
    $converter = new FakePngToPdfConverter;

    expect($converter)->toBeInstanceOf(Converter::class);
    expect($converter->key())->toBe('png_to_pdf');
    expect($converter->sourceFormat())->toBe('png');
    expect($converter->targetFormat())->toBe('pdf');
});

it('has fake jpg to webp converter', function () {
    $converter = new FakeJpgToWebpConverter;

    expect($converter)->toBeInstanceOf(Converter::class);
    expect($converter->key())->toBe('jpg_to_webp');
    expect($converter->sourceFormat())->toBe('jpg');
    expect($converter->targetFormat())->toBe('webp');
});
