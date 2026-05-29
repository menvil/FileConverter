<?php

it('defines the mvp converter capability list', function () {
    $capabilities = config('converters.mvp_capabilities');

    expect($capabilities)->toBeArray();
    expect($capabilities)->toContain('png:jpg');
    expect($capabilities)->toContain('png:webp');
    expect($capabilities)->toContain('png:pdf');
    expect($capabilities)->toContain('jpg:png');
    expect($capabilities)->toContain('jpg:webp');
    expect($capabilities)->toContain('jpg:pdf');
    expect($capabilities)->toHaveCount(6);
});

it('does not include non mvp directions in capability list', function () {
    $capabilities = config('converters.mvp_capabilities');

    expect($capabilities)->not->toContain('mp4:mp3');
    expect($capabilities)->not->toContain('pdf:docx');
    expect($capabilities)->not->toContain('docx:pdf');
});
