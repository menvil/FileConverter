<?php

declare(strict_types=1);

namespace App\Support\Api;

use App\Exceptions\Billing\InsufficientCreditsException;
use App\Exceptions\Features\FeatureNotAvailableException;
use App\Exceptions\Files\UnsupportedFileFormatException;
use App\Exceptions\Storage\StorageLimitExceededException;
use App\Support\Conversions\Exceptions\UnsupportedConversionException;
use App\Support\Converters\Exceptions\InvalidConverterOptionsException;
use App\Support\Converters\Exceptions\UnsupportedFormatException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

final class ApiExceptionMapper
{
    public function map(Throwable $e): ?MappedApiError
    {
        return match (true) {
            $e instanceof InsufficientCreditsException => new MappedApiError(
                code: 'insufficient_credits',
                message: $e->getMessage(),
                status: 402,
                details: $e->details(),
            ),
            $e instanceof FeatureNotAvailableException => new MappedApiError(
                code: 'feature_not_available',
                message: $e->getMessage(),
                status: 403,
                details: $e->details(),
            ),
            $e instanceof UnsupportedFormatException => new MappedApiError(
                code: 'unsupported_format',
                message: $e->getMessage(),
                status: 422,
                details: $e->details(),
            ),
            $e instanceof UnsupportedFileFormatException => new MappedApiError(
                code: 'unsupported_format',
                message: $e->getMessage(),
                status: 422,
            ),
            $e instanceof UnsupportedConversionException => new MappedApiError(
                code: 'unsupported_conversion',
                message: $e->getMessage(),
                status: 422,
                details: $e->details(),
            ),
            $e instanceof InvalidConverterOptionsException => new MappedApiError(
                code: 'invalid_options',
                message: $e->getMessage(),
                status: 422,
                details: $e->fieldErrors(),
            ),
            $e instanceof StorageLimitExceededException => new MappedApiError(
                code: 'storage_limit_exceeded',
                message: $e->getMessage(),
                status: 413,
                details: $e->details(),
            ),
            $e instanceof AuthenticationException => new MappedApiError(
                code: 'unauthorized',
                message: 'Unauthenticated.',
                status: 401,
            ),
            $e instanceof AuthorizationException => new MappedApiError(
                code: 'forbidden',
                message: 'Forbidden.',
                status: 403,
            ),
            $e instanceof ValidationException => new MappedApiError(
                code: 'validation_failed',
                message: $e->getMessage(),
                status: 422,
                details: $e->errors(),
            ),
            $e instanceof ThrottleRequestsException => new MappedApiError(
                code: 'rate_limited',
                message: 'Too many requests.',
                status: 429,
            ),
            $e instanceof HttpException && $e->getStatusCode() === 401 => new MappedApiError(
                code: 'unauthorized',
                message: 'Unauthenticated.',
                status: 401,
            ),
            $e instanceof HttpException && $e->getStatusCode() === 403 => new MappedApiError(
                code: 'forbidden',
                message: 'Forbidden.',
                status: 403,
            ),
            $e instanceof HttpException && $e->getStatusCode() === 404 => new MappedApiError(
                code: 'not_found',
                message: 'Resource not found.',
                status: 404,
            ),
            default => null,
        };
    }
}
