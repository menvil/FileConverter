<?php

namespace App\Support\Files;

use App\Exceptions\Files\UnsupportedFileFormatException;
use Illuminate\Http\UploadedFile;

final class FileFormatDetector
{
    private const MIME_TO_FORMAT = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    private const EXTENSION_TO_FORMAT = [
        'png' => 'png',
        'jpg' => 'jpg',
        'jpeg' => 'jpg',
        'webp' => 'webp',
        'pdf' => 'pdf',
    ];

    public function detect(UploadedFile $file): string
    {
        $mime = $file->getMimeType();

        if ($mime !== null && isset(self::MIME_TO_FORMAT[$mime])) {
            return self::MIME_TO_FORMAT[$mime];
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());

        if ($extension !== '' && isset(self::EXTENSION_TO_FORMAT[$extension])) {
            return self::EXTENSION_TO_FORMAT[$extension];
        }

        throw UnsupportedFileFormatException::forFile($file->getClientOriginalName(), $mime);
    }
}
