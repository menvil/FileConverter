<?php

declare(strict_types=1);

it('documents converters index endpoint', function () {
    $paths = openApiSpec()['paths'];

    expect($paths)->toHaveKey('/converters')
        ->and($paths['/converters'])->toHaveKey('get');
});

it('documents converter schema endpoint', function () {
    $paths = openApiSpec()['paths'];

    expect($paths)->toHaveKey('/converters/{source}/{target}/schema')
        ->and($paths['/converters/{source}/{target}/schema'])->toHaveKey('get');
});

it('documents file upload endpoint', function () {
    $paths = openApiSpec()["paths"];
    expect($paths)->toHaveKey("/files")
        ->and($paths["/files"])->toHaveKey("post");
});

it('documents file target formats endpoint', function () {
    $paths = openApiSpec()["paths"];
    expect($paths)->toHaveKey("/files/{file}/targets")
        ->and($paths["/files/{file}/targets"])->toHaveKey("get");
});

it('documents conversion cost estimate endpoint', function () {
    $paths = openApiSpec()["paths"];
    expect($paths)->toHaveKey("/conversions/estimate")
        ->and($paths["/conversions/estimate"])->toHaveKey("post");
});
