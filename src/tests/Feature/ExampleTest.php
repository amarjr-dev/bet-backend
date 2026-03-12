<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_a_api_esta_acessivel(): void
    {
        $response = $this->get('/up');

        $response->assertStatus(200);
    }
}
