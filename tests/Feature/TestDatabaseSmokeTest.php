<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TestDatabaseSmokeTest extends TestCase
{
    public function test_can_use_the_test_database(): void
    {
        $this->assertNotEmpty(DB::connection()->getDatabaseName());
    }
}
