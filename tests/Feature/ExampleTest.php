<?php

namespace Tests\Feature;

use Database\Seeders\ConfiguracaoSeeder;
use Database\Seeders\TextoSistemaSeeder;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->seed(TextoSistemaSeeder::class);
        $this->seed(ConfiguracaoSeeder::class);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
