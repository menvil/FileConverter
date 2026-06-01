<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Tests\Support\ImageFixture;

it('creates png image driver test fixture', function () {
    Storage::fake('local');

    $pngPath = ImageFixture::png('source.png', width: 600, height: 400);

    expect(Storage::disk('local')->exists($pngPath))->toBeTrue();
});

it('creates jpg image driver test fixture', function () {
    Storage::fake('local');

    $jpgPath = ImageFixture::jpg('source.jpg', width: 600, height: 400);

    expect(Storage::disk('local')->exists($jpgPath))->toBeTrue();
});
