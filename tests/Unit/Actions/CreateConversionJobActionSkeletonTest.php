<?php

declare(strict_types=1);

use App\Actions\Conversions\CreateConversionJobAction;

it('has create conversion job action with handle method', function () {
    $action = app(CreateConversionJobAction::class);

    expect(method_exists($action, 'handle'))->toBeTrue();
});
