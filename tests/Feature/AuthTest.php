<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private Rol $adminRol;
    private Usuario $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear rol de administrador
        $this->adminRol = Rol::create([
            'nombre' => 'Administrador',
            'descripcion' => 'Acceso completo',
            'permiso_id' => [],
            'estado' => 'activo',
        ]);

        // Crear usuario de prueba
        $this->usuario = Usuario::create([
            'nombre' => 'Test',
            'apellidos' => 'User',
            'ci' => '12345678',
            'password' => Hash::make('12345678'),
            'telefono' => '70000000',
            'email' => 'test@example.com',
            'rol_id' => $this->adminRol->id,
            'foto' => 'default.jpg',
            'estado' => 'activo',
        ]);
    }

    public function test_login_exitoso(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => '12345678',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'message',
                'token',
                'user' => ['id', 'nombre', 'email', 'rol'],
            ]);
    }

    public function test_login_credenciales_invalidas(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password_incorrecto',
        ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    public function test_login_usuario_no_existe(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'noexiste@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    public function test_bloqueo_por_intentos_fallidos(): void
    {
        // Realizar 5 intentos fallidos
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'test@example.com',
                'password' => 'password_incorrecto',
            ]);
        }

        // El 6to intento fallará por throttling de Laravel (antes de llegar a la lógica del controlador)
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password_incorrecto',
        ]);

        // Esperamos 429 (Too Many Requests) del middleware de throttle
        $response->assertStatus(429);
    }

    public function test_logout_exitoso(): void
    {
        // Login primero
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => '12345678',
        ]);

        $token = $loginResponse['token'];

        // Logout con token
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_me_obtener_usuario_autenticado(): void
    {
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => '12345678',
        ]);

        $token = $loginResponse['token'];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user' => [
                    'email' => 'test@example.com',
                    'nombre' => 'Test',
                ],
            ]);
    }

    public function test_sin_autenticacion_retorna_401(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }
}
