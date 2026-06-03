<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('has api keys table', function () {
    expect(Schema::hasTable('api_keys'))->toBeTrue();

    foreach ([
        'id',
        'user_id',
        'name',
        'token_hash',
        'last_used_at',
        'revoked_at',
        'created_at',
        'updated_at',
    ] as $column) {
        expect(Schema::hasColumn('api_keys', $column))->toBeTrue("Missing column: {$column}");
    }
});
