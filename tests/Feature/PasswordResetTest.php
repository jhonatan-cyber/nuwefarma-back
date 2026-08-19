<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private string $email = 'juan.perez@example.com';

    private string $password = 'password123';

    private Usuario $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $rol = Rol::create([
            'nombre' => 'Administrador',
            'descripcion' => 'Acceso completo',
            'permiso_id' => [],
            'estado' => 'activo',
        ]);

        $this->usuario = Usuario::create([
            'nombre' => 'Juan',
            'apellidos' => 'Perez',
            'ci' => '12345678',
            'password' => Hash::make($this->password),
            'telefono' => '70000000',
            'email' => $this->email,
            'rol_id' => $rol->id,
            'foto' => 'default.jpg',
            'estado' => 'activo',
        ]);
    }

    public function test_puede_solicitar_token_de_recuperacion(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $this->email,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['token']);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $this->email,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'accion' => 'forgot_password',
            'usuario_id' => $this->usuario->id,
        ]);
    }

    public function test_no_puede_solicitar_token_para_email_inexistente(): void
    {
        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'noexiste@example.com',
        ])->assertStatus(404);
    }

    public function test_falta_email_al_solicitar_recuperacion_devuelve_422(): void
    {
        $this->postJson('/api/v1/auth/forgot-password', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_puede_resetear_password_con_token_valido(): void
    {
        $token = $this->postJson('/api/v1/auth/forgot-password', ['email' => $this->email])->json('token');

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => $this->email,
            'token' => $token,
            'password' => 'nueva-clave-123',
            'password_confirmation' => 'nueva-clave-123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $this->email]);

        $this->assertDatabaseHas('activity_logs', [
            'accion' => 'password_reset',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $this->email,
            'password' => 'nueva-clave-123',
        ])->assertStatus(200);

        $this->postJson('/api/v1/auth/login', [
            'email' => $this->email,
            'password' => $this->password,
        ])->assertStatus(422);
    }

    public function test_no_puede_resetear_con_token_invalido(): void
    {
        $this->postJson('/api/v1/auth/forgot-password', ['email' => $this->email]);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $this->email,
            'token' => 'token-equivocado',
            'password' => 'nueva-clave-123',
            'password_confirmation' => 'nueva-clave-123',
        ])->assertStatus(400)
            ->assertJsonPath('success', false);
    }

    public function test_no_puede_resetear_con_token_expirado(): void
    {
        $token = $this->postJson('/api/v1/auth/forgot-password', ['email' => $this->email])->json('token');

        DB::table('password_reset_tokens')
            ->where('email', $this->email)
            ->update(['expires_at' => now()->subHour()]);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $this->email,
            'token' => $token,
            'password' => 'nueva-clave-123',
            'password_confirmation' => 'nueva-clave-123',
        ])->assertStatus(400)
            ->assertJsonPath('success', false);

        $this->assertTrue(Hash::check($this->password, $this->usuario->fresh()->password));
    }

    public function test_no_puede_resetear_sin_confirmar_password(): void
    {
        $token = $this->postJson('/api/v1/auth/forgot-password', ['email' => $this->email])->json('token');

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $this->email,
            'token' => $token,
            'password' => 'nueva-clave-123',
        ])->assertStatus(422);
    }

    public function test_puede_cambiar_password_estando_autenticado(): void
    {
        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $this->email,
            'password' => $this->password,
        ])->json('data.token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/change-password', [
                'password_actual' => $this->password,
                'nueva_password' => 'clave-nueva-456',
                'nueva_password_confirmation' => 'clave-nueva-456',
            ])->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('clave-nueva-456', $this->usuario->fresh()->password));

        $this->assertDatabaseHas('activity_logs', [
            'accion' => 'change_password',
            'usuario_id' => $this->usuario->id,
        ]);
    }

    public function test_no_puede_cambiar_password_con_password_actual_incorrecta(): void
    {
        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $this->email,
            'password' => $this->password,
        ])->json('data.token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/change-password', [
                'password_actual' => 'password-incorrecta',
                'nueva_password' => 'clave-nueva-456',
                'nueva_password_confirmation' => 'clave-nueva-456',
            ])->assertStatus(400)
            ->assertJsonPath('success', false);

        $this->assertTrue(Hash::check($this->password, $this->usuario->fresh()->password));
    }

    public function test_no_puede_cambiar_password_a_la_misma_clave_actual(): void
    {
        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $this->email,
            'password' => $this->password,
        ])->json('data.token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/change-password', [
                'password_actual' => $this->password,
                'nueva_password' => $this->password,
                'nueva_password_confirmation' => $this->password,
            ])->assertStatus(400);
    }

    public function test_no_puede_cambiar_password_sin_autenticacion(): void
    {
        $this->postJson('/api/v1/auth/change-password', [
            'password_actual' => $this->password,
            'nueva_password' => 'clave-nueva-456',
            'nueva_password_confirmation' => 'clave-nueva-456',
        ])->assertStatus(401);
    }
}