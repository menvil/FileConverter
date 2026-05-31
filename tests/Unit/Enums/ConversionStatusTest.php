<?php

declare(strict_types=1);

use App\Enums\ConversionStatus;

it('defines conversion job statuses', function () {
    expect(ConversionStatus::Draft->value)->toBe('draft');
    expect(ConversionStatus::Queued->value)->toBe('queued');
    expect(ConversionStatus::Processing->value)->toBe('processing');
    expect(ConversionStatus::Completed->value)->toBe('completed');
    expect(ConversionStatus::Failed->value)->toBe('failed');
    expect(ConversionStatus::Cancelled->value)->toBe('cancelled');
    expect(ConversionStatus::Expired->value)->toBe('expired');
});
