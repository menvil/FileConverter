<?php

declare(strict_types=1);

use App\Support\Api\ApiErrorResponseFactory;

it('returns standard api error response shape', function () {
    $factory = app(ApiErrorResponseFactory::class);

    $response = $factory->make(
        code: 'test_error',
        message: 'Test error message.',
        status: 422,
        details: ['field' => 'value'],
    );

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true))->toBe([
        'error' => [
            'code' => 'test_error',
            'message' => 'Test error message.',
            'details' => ['field' => 'value'],
        ],
    ]);
});

it('defaults to empty details and 400 status', function () {
    $factory = app(ApiErrorResponseFactory::class);

    $response = $factory->make(code: 'some_error', message: 'Something went wrong.');

    expect($response->getStatusCode())->toBe(400);
    $data = $response->getData(true);
    expect($data['error']['details'])->toBe([]);
});
