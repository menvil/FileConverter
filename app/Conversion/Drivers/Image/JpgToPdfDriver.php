<?php

declare(strict_types=1);

namespace App\Conversion\Drivers\Image;

use App\Conversion\Drivers\Image\Concerns\RendersSingleImagePdf;
use App\Support\Conversions\Contracts\ConverterDriver;
use App\Support\Conversions\DTO\ConversionContext;
use App\Support\Conversions\DTO\ConversionResult;

final class JpgToPdfDriver implements ConverterDriver
{
    use RendersSingleImagePdf;

    public function key(): string
    {
        return 'jpg_to_pdf';
    }

    public function convert(ConversionContext $context): ConversionResult
    {
        return $this->renderPdf($context, 'image/jpeg');
    }
}
