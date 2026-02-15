<?php

namespace Tests\Feature;

use App\Models\Sucursal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAuthenticatedUser;
use Tests\TestCase;

class CajaTest extends TestCase
{
    use CreatesAuthenticatedUser, RefreshDatabase;

    private string $token;

    private \App\Models\Usuario $adminUser;

    private Sucursal $sucursal;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear rol y usuario directamente
        $rol = \App\Models\Rol::create([
            'nombre' => 'Administrador',
            'descripcion' => 'Acceso completo',
            'estado' => 'activo',
        ]);

        $this->adminUser = \App\Models\Usuario::create([
            'nombre' => 'Test',
            'apellidos' => 'Admin',
            'ci' => '12345678',
            'password' => \Illuminate\Support\Facades\Hash::make('12345678'),
            'telefono' => '70000000',
            'email' => 'admin@test.com',
            'rol_id' => $rol->id,
            'foto' => 'default.jpg',
            'estado' => 'activo',
        ]);

        // Login directo
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'admin@test.com',
            'password' => '12345678',
        ]);

        $this->token = $loginResponse['data']['token'];

        $this->sucursal = \App\Models\Sucursal::create([
            'nombre' => 'Sucursal Test',
            'direccion' => 'Calle Test 123',
            'ciudad' => 'La Paz',
            'pais' => 'Bolivia',
            'telefono' => '70000001',
            'email' => 'sucursal@test.com',
            'estado' => 'activo',
        ]);
    }

    public function test_puede_listar_cajas(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson('/api/cajas');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'nombre',
                        'numero_caja',
                        'estado',
                        'sucursal' => ['nombre'],
                    ],
                ],
            ]);
    }

    public function test_puede_crear_caja(): void
    {
        $cajaData = [
            'nombre' => 'Caja Nueva',
            'numero_caja' => 2,
            'sucursal_id' => $this->sucursal->id,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/cajas', $cajaData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'nombre',
                    'numero_caja',
                    'estado',
                ],
            ]);

        $this->assertDatabaseHas('cajas', [
            'nombre' => 'Caja Nueva',
            'numero_caja' => 2,
        ]);
    }

    public function test_puede_abrir_caja(): void
    {
        $caja = \App\Models\Caja::create([
            'nombre' => 'Caja Test',
            'numero_caja' => 3,
            'sucursal_id' => $this->sucursal->id,
            'estado' => 'cerrada',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->patchJson("/api/cajas/{$caja->id}/abrir");

        $response->assertStatus(200)
            ->assertJsonPath('data.estado', 'abierta');
    }

    public function test_puede_cerrar_caja(): void
    {
        $caja = \App\Models\Caja::create([
            'nombre' => 'Caja Test',
            'numero_caja' => 4,
            'sucursal_id' => $this->sucursal->id,
            'estado' => 'abierta',
            'monto_inicial' => 100.00,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->patchJson("/api/cajas/{$caja->id}/cerrar", [
            'monto_final' => 500.00,
            'observaciones' => 'Cierre de caja',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.estado', 'cerrada');
    }

    public function test_no_puede_crear_caja_sin_sucursal(): void
    {
        $cajaData = [
            'nombre' => 'Caja Sin Sucursal',
            'numero_caja' => 5,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/cajas', $cajaData);

        $response->assertStatus(422);
    }

    public function test_no_puede_abrir_caja_ya_abierta(): void
    {
        $caja = \App\Models\Caja::create([
            'nombre' => 'Caja Test',
            'numero_caja' => 6,
            'sucursal_id' => $this->sucursal->id,
            'estado' => 'abierta',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->patchJson("/api/cajas/{$caja->id}/abrir");

        $response->assertStatus(400)
            ->assertJsonPath('success', false);
    }

    public function test_usuario_no_autenticado_no_puede_acceder(): void
    {
        $response = $this->getJson('/api/cajas');

        $response->assertStatus(401);
    }
}
