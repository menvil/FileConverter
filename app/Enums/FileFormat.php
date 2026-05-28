<?php

namespace App\Enums;

use App\Support\Converters\Exceptions\UnsupportedFormatException;

enum FileFormat: string
{
    case Png = 'png';
    case Jpg = 'jpg';
    case Webp = 'webp';
    case Pdf = 'pdf';

    public static function normalize(string $input): string
    {
        $value = ltrim(strtolower(trim($input)), '.');

        if ($value === 'jpeg') {
            return self::Jpg->value;
        }

        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case->value;
            }
        }

        throw UnsupportedFormatException::forInput($input);
    }
}
