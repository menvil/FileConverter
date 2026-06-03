<?php

use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\EnsureApiAccessIsAllowed;
use App\Support\Api\ApiErrorResponseFactory;
use App\Support\Api\ApiExceptionMapper;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
        ]);

        $middleware->alias([
            'api.key' => AuthenticateApiKey::class,
            'api.access' => EnsureApiAccessIsAllowed::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $mapped = app(ApiExceptionMapper::class)->map($e);

            if ($mapped === null) {
                return null;
            }

            $response = app(ApiErrorResponseFactory::class)->make(
                code: $mapped->code,
                message: $mapped->message,
                status: $mapped->status,
                details: $mapped->details,
            );

            if ($e instanceof ThrottleRequestsException) {
                foreach ($e->getHeaders() as $header => $value) {
                    $response->header($header, $value);
                }
            }

            return $response;
        });
    })->create();
