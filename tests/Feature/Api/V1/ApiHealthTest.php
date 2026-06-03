<?php

declare(strict_types=1);

use Illuminate\Testing\Fluent\AssertableJson;

it('responds from api v1 health endpoint', function () {
    $this->getJson('/api/v1/health')
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('status', 'ok')
            ->where('version', 'v1')
        );
});
