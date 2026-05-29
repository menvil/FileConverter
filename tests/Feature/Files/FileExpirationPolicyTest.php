<?php

use App\Models\User;
use App\Support\Files\FileExpirationPolicy;
use Carbon\CarbonInterface;

it('returns a future uploaded file expiration', function () {
    $user = User::factory()->create();

    $expiresAt = app(FileExpirationPolicy::class)->forUploadedFile($user);

    expect($expiresAt)->toBeInstanceOf(CarbonInterface::class);
    expect($expiresAt->greaterThan(now()))->toBeTrue();
});

it('returns a future result file expiration', function () {
    $user = User::factory()->create();

    $expiresAt = app(FileExpirationPolicy::class)->forResultFile($user);

    expect($expiresAt)->toBeInstanceOf(CarbonInterface::class);
    expect($expiresAt->greaterThan(now()))->toBeTrue();
});
