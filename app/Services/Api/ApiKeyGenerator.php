<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Support\Str;

final class ApiKeyGenerator
{
    public function create(User $user, string $name): GeneratedApiKey
    {
        $plainToken = 'fc_'.Str::random(64);
        $hash = hash('sha256', $plainToken);

        $apiKey = ApiKey::create([
            'user_id' => $user->id,
            'name' => $name,
            'token_hash' => $hash,
        ]);

        return new GeneratedApiKey(apiKey: $apiKey, plainToken: $plainToken);
    }
}
