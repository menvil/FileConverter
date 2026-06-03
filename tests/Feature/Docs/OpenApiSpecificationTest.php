<?php

declare(strict_types=1);

it('has an openapi specification file', function () {
    expect(base_path('docs/api/openapi.yaml'))->toBeFile();
});
