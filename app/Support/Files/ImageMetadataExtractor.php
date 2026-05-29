<?php

namespace App\Support\Files;

use App\Exceptions\Files\FileMetadataException;
use Illuminate\Http\UploadedFile;

final class ImageMetadataExtractor
{
    private const IMAGE_FORMATS = ['png', 'jpg', 'webp'];

    public function extract(UploadedFile $file, string $format): array
    {
        if (! in_array($format, self::IMAGE_FORMATS, true)) {
            return [];
        }

        $path = $file->getRealPath();

        if ($path === false) {
            throw FileMetadataException::cannotReadImage($file->getClientOriginalName());
        }

        $size = @getimagesize($path);

        if ($size === false) {
            throw FileMetadataException::cannotReadImage($file->getClientOriginalName());
        }

        return [
            'width' => $size[0],
            'height' => $size[1],
            'supports_transparency' => $this->formatSupportsTransparency($format),
            'orientation' => null,
        ];
    }

    private function formatSupportsTransparency(string $format): bool
    {
        return $format !== 'jpg';
    }
}
