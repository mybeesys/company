<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // This app doesn't necessarily serve '/' (multi-tenant + auth). Laravel 11 health route is always present.
        $response = $this->get('/up');

        $response->assertStatus(200);
    }
}
