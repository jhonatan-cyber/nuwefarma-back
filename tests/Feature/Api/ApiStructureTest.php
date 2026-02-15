<?php

namespace Tests\Feature\Api;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiStructureTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = Usuario::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
    }

    /** @test */
    public function api_info_returns_successful_response()
    {
        $response = $this->getJson('/api/info');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'name',
                'version',
                'laravel_version',
                'php_version',
            ]);
    }

    /** @test */
    public function health_check_returns_successful_response()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'status',
                    'timestamp',
                    'services' => [
                        'database',
                        'cache',
                        'storage',
                    ],
                ],
            ]);
    }

    /** @test */
    public function productos_routes_exist()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/productos');

        $response->assertStatus(200);
    }

    /** @test */
    public function ventas_routes_exist()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/ventas');

        $response->assertStatus(200);
    }

    /** @test */
    public function rutas_no_tienen_versionamiento()
    {
        $routes = [
            '/api/productos',
            '/api/ventas',
            '/api/clientes',
            '/api/categorias',
            '/api/health',
        ];

        foreach ($routes as $route) {
            $this->assertStringNotContainsString('/v1/', $route);
            $this->assertStringNotContainsString('/v2/', $route);
        }
    }
}
