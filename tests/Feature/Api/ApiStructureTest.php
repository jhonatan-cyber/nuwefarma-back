<?php

namespace Tests\Feature\Api;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function api_info_returns_successful_response()
    {
        $response = $this->getJson('/api/v1/info');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'request_id',
                'data' => [
                    'name',
                    'version',
                    'laravel_version',
                    'php_version',
                ],
            ]);

        $features = $response->json('data.features');

        $this->assertArrayNotHasKey('ai_integration', $features);
        $this->assertArrayNotHasKey('real_time', $features);
        $this->assertArrayNotHasKey('webhooks', $features);
    }

    #[Test]
    public function health_check_returns_successful_response()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/health');

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

    #[Test]
    public function productos_routes_exist()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/productos');

        $response->assertStatus(200);
    }

    #[Test]
    public function ventas_routes_exist()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/ventas');

        $response->assertStatus(200);
    }

    #[Test]
    public function rutas_utilizan_versionamiento_v1()
    {
        $routes = [
            '/api/v1/productos',
            '/api/v1/ventas',
            '/api/v1/clientes',
            '/api/v1/categorias',
            '/api/v1/health',
        ];

        foreach ($routes as $route) {
            $this->assertStringContainsString('/v1/', $route);
            $this->assertStringNotContainsString('/v2/', $route);
        }
    }
}
