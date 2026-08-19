<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClienteCrudTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $user;

    protected function setUp(): void
    {
        parent::setUp();

        $rol = Rol::create([
            'nombre' => 'Administrador',
            'descripcion' => 'Administrador del sistema',
            'estado' => 'activo',
        ]);

        $this->user = Usuario::factory()->create([
            'rol_id' => $rol->id,
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_lista_clientes_devuelve_coleccion_paginada(): void
    {
        Cliente::create([
            'nombre' => 'Juan',
            'apellidos' => 'Perez',
            'ci' => '123456',
            'telefono' => '70000001',
            'estado' => 'activo',
        ]);

        $response = $this->getJson('/api/v1/clientes');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'data',
                    'meta',
                    'links',
                ],
            ]);
    }

    public function test_puede_crear_cliente_con_campos_basicos(): void
    {
        $payload = [
            'nombre' => 'Maria',
            'apellidos' => 'Lopez',
            'ci' => '654321',
            'telefono' => '70000002',
            'estado' => 'activo',
        ];

        $response = $this->postJson('/api/v1/clientes', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nombre', 'Maria')
            ->assertJsonPath('data.apellidos', 'Lopez');

        $this->assertDatabaseHas('clientes', [
            'nombre' => 'Maria',
            'apellidos' => 'Lopez',
            'ci' => '654321',
        ]);
    }

    public function test_puede_togglear_estado_cliente(): void
    {
        $cliente = Cliente::create([
            'nombre' => 'Carlos',
            'apellidos' => 'Gomez',
            'ci' => '777888',
            'telefono' => '70000003',
            'estado' => 'activo',
        ]);

        $response = $this->patchJson("/api/v1/clientes/{$cliente->id}/toggle-estado");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $cliente->id)
            ->assertJsonPath('data.estado', 'inactivo');

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            'estado' => 'inactivo',
        ]);
    }

    public function test_puede_actualizar_cliente(): void
    {
        $cliente = Cliente::create([
            'nombre' => 'Pedro',
            'apellidos' => 'Suarez',
            'ci' => '445566',
            'telefono' => '70000005',
            'estado' => 'activo',
        ]);

        $payload = [
            'nombre' => 'Pedro Pablo',
            'apellidos' => 'Suarez Flores',
            'ci' => '445566',
            'telefono' => '71111111',
            'estado' => 'inactivo',
        ];

        $response = $this->putJson("/api/v1/clientes/{$cliente->id}", $payload);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $cliente->id)
            ->assertJsonPath('data.nombre', 'Pedro Pablo')
            ->assertJsonPath('data.apellidos', 'Suarez Flores')
            ->assertJsonPath('data.estado', 'inactivo');

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            'nombre' => 'Pedro Pablo',
            'apellidos' => 'Suarez Flores',
            'telefono' => '71111111',
            'estado' => 'inactivo',
        ]);
    }

    public function test_puede_eliminar_cliente_sin_relaciones(): void
    {
        $cliente = Cliente::create([
            'nombre' => 'Lucia',
            'apellidos' => 'Rojas',
            'ci' => '112233',
            'telefono' => '70000004',
            'estado' => 'activo',
        ]);

        $response = $this->deleteJson("/api/v1/clientes/{$cliente->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('clientes', [
            'id' => $cliente->id,
        ]);
    }
}
