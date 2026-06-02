<?php

it('has cashier webhook endpoint registered', function () {
    $response = $this->postJson('/stripe/webhook', []);

    expect($response->status())->not->toBe(404);
});
