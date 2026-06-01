<?php

declare(strict_types=1);

namespace App\Conversion\Drivers\Image\Concerns;

use App\Support\Conversions\DTO\ConversionContext;
use App\Support\Conversions\DTO\ConversionResult;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

trait RendersSingleImagePdf
{
    private function renderPdf(ConversionContext $context, string $imageMimeType): ConversionResult
    {
        $sourceContent = Storage::disk('local')->get($context->sourceFile->stored_path);
        $imageDataUri = "data:{$imageMimeType};base64,".base64_encode($sourceContent);

        $pageSize = $context->options['page_size'] ?? 'a4';
        $orientation = $this->resolveOrientation($context->options['orientation'] ?? 'auto', $sourceContent);
        $margin = $this->resolveMargin($context->options['margin'] ?? 'small');
        $fitMode = $context->options['fit_mode'] ?? 'contain';

        $html = view('pdf.single-image', compact('imageDataUri', 'margin', 'fitMode'))->render();

        $pdf = Pdf::loadHTML($html)->setPaper($pageSize, $orientation);

        $outputPath = $context->outputDirectory.'/result.pdf';
        Storage::disk('local')->put($outputPath, $pdf->output());

        return new ConversionResult(
            path: $outputPath,
            originalName: 'result.pdf',
            mimeType: 'application/pdf',
            extension: 'pdf',
            sizeBytes: Storage::disk('local')->size($outputPath),
        );
    }

    private function resolveOrientation(string $orientation, string $imageContent): string
    {
        if ($orientation !== 'auto') {
            return $orientation;
        }

        $image = imagecreatefromstring($imageContent);
        if ($image === false) {
            return 'portrait';
        }

        $width = imagesx($image);
        $height = imagesy($image);
        imagedestroy($image);

        return $width > $height ? 'landscape' : 'portrait';
    }

    private function resolveMargin(string $margin): string
    {
        return match ($margin) {
            'none' => '0mm',
            'small' => '10mm',
            'medium' => '20mm',
            'large' => '30mm',
            default => '10mm',
        };
    }
}
