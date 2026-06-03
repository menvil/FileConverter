<?php

declare(strict_types=1);

it('has an openapi specification file', function () {
    expect(base_path('docs/api/openapi.yaml'))->toBeFile();
});

it('parses openapi specification', function () {
    $spec = openApiSpec();

    expect($spec)->toBeArray()
        ->and($spec)->toHaveKey('openapi')
        ->and($spec)->toHaveKey('info')
        ->and($spec)->toHaveKey('paths')
        ->and($spec)->toHaveKey('components');
});
