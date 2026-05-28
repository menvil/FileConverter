<?php

namespace Tests\Feature\Livewire;

use App\Livewire\TestPing;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireSmokeTest extends TestCase
{
    public function test_renders_test_livewire_component(): void
    {
        Livewire::test(TestPing::class)
            ->assertSee('Livewire is working');
    }
}
