<?php

declare(strict_types=1);

it('has cashier webhook endpoint registered', function () {
    $response = $this->postJson('/stripe/webhook', []);

    expect($response->status())->not->toBe(404);
});

it('stripe webhook endpoint is exempt from csrf protection', function () {
    // Simulate an external POST without a session-backed CSRF token,
    // exactly as Stripe delivers webhooks in production.
    $response = $this->call(
        method: 'POST',
        uri: '/stripe/webhook',
        parameters: [],
        cookies: [],
        files: [],
        server: ['CONTENT_TYPE' => 'application/json'],
        content: '{}',
    );

    // Must not be rejected with 419 (CSRF token mismatch).
    expect($response->getStatusCode())->not->toBe(419);
});
