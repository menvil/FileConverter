<?php

namespace App\Support\Files;

final class UploadedFileRules
{
    public const MAX_FILE_KILOBYTES = 10240;

    /**
     * @return array<int, string>
     */
    public static function rules(): array
    {
        return [
            'required',
            'file',
            'max:'.self::MAX_FILE_KILOBYTES,
            'mimes:png,jpg,jpeg,webp,pdf',
        ];
    }
}
