<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RolTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $rol = Rol::create([
            'nombre' => 'Administrador',
            'descripcion' => 'Acceso completo',
            'permiso_id' => [],
            'estado' => 'activo',
        ]);

        Usuario::create([
            'nombre' => 'Test',
            'apellidos' => 'Admin',
            'ci' => '12345678',
            'password' => Hash::make('password123'),
            'telefono' => '70000000',
            'email' => 'admin@test.com',
            'rol_id' => $rol->id,
            'foto' => 'default.jpg',
            'estado' => 'activo',
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $this->token = $loginResponse['data']['token'];
    }

    public function test_stats_overview_roles(): void
    {
        $gerente = Rol::create([
            'nombre' => 'Gerente',
            'descripcion' => 'Gestión',
            'permiso_id' => [],
            'estado' => 'activo',
        ]);

        Usuario::create([
            'nombre' => 'Gerente',
            'apellidos' => 'Uno',
            'ci' => '87654321',
            'password' => Hash::make('password123'),
            'telefono' => '70000003',
            'email' => 'gerente@test.com',
            'rol_id' => $gerente->id,
            'foto' => 'default.jpg',
            'estado' => 'activo',
        ]);

        Rol::create([
            'nombre' => 'Inactivo',
            'descripcion' => 'Rol inactivo',
            'permiso_id' => [],
            'estado' => 'inactivo',
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$this->token])
            ->getJson('/api/v1/roles/stats/overview');

        $response->assertStatus(200)
            ->assertJsonPath('data.resumen.total', 3)
            ->assertJsonPath('data.resumen.activos', 2)
            ->assertJsonPath('data.resumen.inactivos', 1)
            ->assertJsonPath('data.resumen.total_usuarios', 2)
            ->assertJsonPath('data.resumen.con_usuarios', 2);

        $response->assertJsonCount(3, 'data.usuarios_por_rol');
    }
}