<?php

declare(strict_types=1);

namespace App\Support\UI;

final readonly class UiErrorMessage
{
    public function __construct(
        public string $title,
        public string $message,
        public ?string $actionLabel = null,
        public ?string $actionUrl = null,
    ) {}
}
