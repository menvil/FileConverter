<?php

declare(strict_types=1);

it('documents converters index endpoint', function () {
    $paths = openApiSpec()['paths'];

    expect($paths)->toHaveKey('/converters')
        ->and($paths['/converters'])->toHaveKey('get');
});
