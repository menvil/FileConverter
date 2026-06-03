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

it('documents bearer api key authentication', function () {
    $spec = openApiSpec();

    expect($spec['components']['securitySchemes'])->toHaveKey('ApiKeyBearer')
        ->and($spec['components']['securitySchemes']['ApiKeyBearer']['type'])->toBe('http')
        ->and($spec['components']['securitySchemes']['ApiKeyBearer']['scheme'])->toBe('bearer');
});

it('documents standard api error response schema', function () {
    $schemas = openApiSpec()['components']['schemas'];

    expect($schemas)->toHaveKey('ErrorResponse')
        ->and($schemas['ErrorResponse']['properties'])->toHaveKey('error');
});
