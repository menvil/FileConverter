<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\FeatureAccess\FeatureAccessService;
use App\Support\Api\ApiErrorResponseFactory;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureApiAccessIsAllowed
{
    public function __construct(
        private readonly FeatureAccessService $featureAccess,
        private readonly ApiErrorResponseFactory $errorFactory,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $this->featureAccess->allows($user, 'api_access')) {
            return $this->errorFactory->make(
                code: 'api_not_available',
                message: 'API access is not available on your current plan.',
                status: 403,
            );
        }

        return $next($request);
    }
}
