<?php

declare(strict_types=1);

namespace App\Support\UI;

use App\Exceptions\Billing\InsufficientCreditsException;
use App\Exceptions\Conversions\ConversionFailedException;
use App\Exceptions\Conversions\ConversionResultExpiredException;
use App\Exceptions\Features\FeatureNotAvailableException;
use App\Exceptions\Files\FileTooLargeException;
use App\Exceptions\Files\UnsupportedFileFormatException;
use App\Exceptions\Storage\StorageLimitExceededException;
use App\Support\Conversions\Exceptions\UnsupportedConversionException;
use App\Support\Converters\Exceptions\InvalidConverterOptionsException;
use App\Support\Converters\Exceptions\UnsupportedFormatException;
use Throwable;

final class UiDomainErrorMapper
{
    public function map(Throwable $e): UiErrorMessage
    {
        return match (true) {
            $e instanceof UnsupportedFormatException,
            $e instanceof UnsupportedFileFormatException => new UiErrorMessage(
                title: 'Unsupported file format',
                message: 'This file type is not supported. Upload PNG, JPG, WEBP or PDF.',
            ),
            $e instanceof UnsupportedConversionException => new UiErrorMessage(
                title: 'Conversion not supported',
                message: 'This conversion is not supported yet. Please choose a different format.',
            ),
            $e instanceof InvalidConverterOptionsException => new UiErrorMessage(
                title: 'Invalid settings',
                message: 'Some conversion settings are invalid. Please review the options.',
            ),
            $e instanceof FileTooLargeException => new UiErrorMessage(
                title: 'File too large',
                message: 'This file is too large for your current plan.',
            ),
            $e instanceof StorageLimitExceededException => new UiErrorMessage(
                title: 'Storage limit reached',
                message: 'Your storage limit is reached. Delete some files to upload more.',
            ),
            $e instanceof InsufficientCreditsException => new UiErrorMessage(
                title: 'Not enough credits',
                message: 'You do not have enough credits for this conversion.',
                actionLabel: 'Get more credits',
                actionUrl: '/billing',
            ),
            $e instanceof FeatureNotAvailableException => new UiErrorMessage(
                title: 'Feature unavailable',
                message: 'This feature is not available on your current plan.',
                actionLabel: 'Upgrade plan',
                actionUrl: '/billing',
            ),
            $e instanceof ConversionFailedException => new UiErrorMessage(
                title: 'Conversion failed',
                message: 'Conversion failed. Try again or upload another file.',
            ),
            $e instanceof ConversionResultExpiredException => new UiErrorMessage(
                title: 'Result expired',
                message: 'This result expired. Upload the file again to convert.',
            ),
            default => new UiErrorMessage(
                title: 'Something went wrong',
                message: 'An unexpected error occurred. Please try again.',
            ),
        };
    }
}
