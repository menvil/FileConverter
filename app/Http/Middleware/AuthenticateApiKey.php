<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Support\Api\ApiErrorResponseFactory;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateApiKey
{
    public function __construct(
        private readonly ApiErrorResponseFactory $errorFactory,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null || $token === '') {
            return $this->unauthorized();
        }

        $hash = hash('sha256', $token);

        $apiKey = ApiKey::where('token_hash', $hash)->first();

        if ($apiKey === null || $apiKey->isRevoked()) {
            return $this->unauthorized();
        }

        $apiKey->update(['last_used_at' => now()]);

        auth()->setUser($apiKey->user);

        return $next($request);
    }

    private function unauthorized(): Response
    {
        return $this->errorFactory->make(
            code: 'unauthorized',
            message: 'Invalid or missing API key.',
            status: 401,
        );
    }
}
