<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;
    
    private Rol $adminRol;
    private Rol $usuarioRol;
    private Usuario $admin;
    private Usuario $usuario;
    private string $adminToken;
    private string $usuarioToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles
        $this->adminRol = Rol::create([
            'nombre' => 'Administrador',
            'descripcion' => 'Acceso completo',
            'permiso_id' => [],
            'estado' => 'activo',
        ]);

        $this->usuarioRol = Rol::create([
            'nombre' => 'Usuario',
            'descripcion' => 'Acceso limitado',
            'permiso_id' => ['leer'],
            'estado' => 'activo',
        ]);

        // Crear usuarios
        $this->admin = Usuario::create([
            'nombre' => 'Admin',
            'apellidos' => 'User',
            'ci' => '11111111',
            'password' => Hash::make('11111111'),
            'telefono' => '70000000',
            'email' => 'admin@example.com',
            'rol_id' => $this->adminRol->id,
            'foto' => 'default.jpg',
            'estado' => 'activo',
        ]);

        $this->usuario = Usuario::create([
            'nombre' => 'Regular',
            'apellidos' => 'User',
            'ci' => '22222222',
            'password' => Hash::make('22222222'),
            'telefono' => '70000000',
            'email' => 'usuario@example.com',
            'rol_id' => $this->usuarioRol->id,
            'foto' => 'default.jpg',
            'estado' => 'activo',
        ]);

        // Login
        $adminLogin = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => '11111111',
        ]);

        $usuarioLogin = $this->postJson('/api/auth/login', [
            'email' => 'usuario@example.com',
            'password' => '22222222',
        ]);

        $this->adminToken = $adminLogin['token'];
        $this->usuarioToken = $usuarioLogin['token'];
    }

    public function test_admin_puede_crear_categoria(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson('/api/categorias', [
                'nombre' => 'Antibióticos',
                'estado' => 'activo',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_usuario_no_puede_crear_categoria(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->usuarioToken}")
            ->postJson('/api/categorias', [
                'nombre' => 'Antibióticos',
                'estado' => 'activo',
            ]);

        $response->assertStatus(403)
            ->assertJson(['success' => false])
            ->assertJsonFragment(['message' => 'No tienes permisos para realizar esta acción']);
    }

    public function test_usuario_puede_listar_categorias(): void
    {
        Categoria::create(['nombre' => 'Test', 'estado' => 'activo']);

        $response = $this->withHeader('Authorization', "Bearer {$this->usuarioToken}")
            ->getJson('/api/categorias');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_admin_puede_eliminar_categoria(): void
    {
        $categoria = Categoria::create(['nombre' => 'Test', 'estado' => 'activo']);

        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->deleteJson("/api/categorias/{$categoria->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_usuario_no_puede_eliminar_categoria(): void
    {
        $categoria = Categoria::create(['nombre' => 'Test', 'estado' => 'activo']);

        $response = $this->withHeader('Authorization', "Bearer {$this->usuarioToken}")
            ->deleteJson("/api/categorias/{$categoria->id}");

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_admin_puede_editar_categoria(): void
    {
        $categoria = Categoria::create(['nombre' => 'Original', 'estado' => 'activo']);

        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->putJson("/api/categorias/{$categoria->id}", [
                'nombre' => 'Editada',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_usuario_no_puede_editar_categoria(): void
    {
        $categoria = Categoria::create(['nombre' => 'Original', 'estado' => 'activo']);

        $response = $this->withHeader('Authorization', "Bearer {$this->usuarioToken}")
            ->putJson("/api/categorias/{$categoria->id}", [
                'nombre' => 'Editada',
            ]);

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_sin_autenticacion_no_puede_crear(): void
    {
        $response = $this->postJson('/api/categorias', [
            'nombre' => 'Antibióticos',
            'estado' => 'activo',
        ]);

        $response->assertStatus(401);
    }
}
