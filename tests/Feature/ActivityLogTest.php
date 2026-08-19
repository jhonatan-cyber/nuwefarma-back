<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    private Usuario $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $rol = Rol::create([
            'nombre' => 'Administrador',
            'descripcion' => 'Acceso completo',
            'permiso_id' => [],
            'estado' => 'activo',
        ]);

        $this->adminUser = Usuario::create([
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

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    public function test_puede_listar_logs_con_filtros(): void
    {
        ActivityLog::registrar('crear_usuario', 'usuarios', 'reg-1', 'Creó un usuario', null, null, $this->adminUser->id);
        ActivityLog::registrar('crear_producto', 'productos', 'reg-2', 'Creó un producto', null, null, $this->adminUser->id);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/activity-logs')
            ->assertStatus(200)
            ->assertJsonPath('pagination.total', 2);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/activity-logs?modulo=productos')
            ->assertStatus(200)
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.modulo', 'productos');
    }

    public function test_puede_ver_mis_propios_logs(): void
    {
        ActivityLog::registrar('crear_usuario', 'usuarios', 'reg-1', 'Creó un usuario', null, null, $this->adminUser->id);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/activity-logs/me')
            ->assertStatus(200)
            ->assertJsonPath('pagination.total', 1);
    }

    public function test_puede_ver_detalle_de_log(): void
    {
        ActivityLog::registrar('crear_usuario', 'usuarios', 'reg-1', 'Creó un usuario', ['antiguo' => 1], ['nuevo' => 2], $this->adminUser->id);

        $log = ActivityLog::first();

        $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/activity-logs/{$log->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.accion', 'crear_usuario')
            ->assertJsonPath('data.datos_nuevos.nuevo', 2);
    }

    public function test_log_no_encontrado_devuelve_404(): void
    {
        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/activity-logs/99999999')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }
}