<?php

namespace App\Enums;

enum Plan: string
{
    case Free = 'free';
    case Pro = 'pro';
    case Max = 'max';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::Pro => 'Pro',
            self::Max => 'Max',
        };
    }
}
