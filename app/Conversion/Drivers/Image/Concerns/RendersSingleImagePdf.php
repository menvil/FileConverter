<?php

declare(strict_types=1);

namespace App\Conversion\Drivers\Image\Concerns;

use App\Support\Conversions\DTO\ConversionContext;
use App\Support\Conversions\DTO\ConversionResult;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use RuntimeException;

trait RendersSingleImagePdf
{
    private function renderPdf(ConversionContext $context, string $imageMimeType): ConversionResult
    {
        $storedPath = $context->sourceFile->stored_path;

        if (! Storage::disk('local')->exists($storedPath)) {
            throw new RuntimeException(
                "RendersSingleImagePdf: source file not found at [{$storedPath}]."
            );
        }

        $sourceContent = Storage::disk('local')->get($storedPath);
        $imageDataUri = "data:{$imageMimeType};base64,".base64_encode($sourceContent);

        $pageSize = $this->resolvePageSize($context->options['page_size'] ?? 'a4');
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

    private function resolvePageSize(string $pageSize): string
    {
        if ($pageSize === 'auto' || $pageSize === '') {
            return 'a4';
        }

        return $pageSize;
    }

    private function resolveOrientation(string $orientation, string $imageContent): string
    {
        if ($orientation !== 'auto') {
            return $orientation;
        }

        $manager = new ImageManager(new Driver);

        try {
            $image = $manager->decodeBinary($imageContent);
        } catch (\Throwable $e) {
            throw new RuntimeException(
                "RendersSingleImagePdf: could not decode image binary to determine orientation. {$e->getMessage()}",
                previous: $e,
            );
        }

        return $image->width() > $image->height() ? 'landscape' : 'portrait';
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
