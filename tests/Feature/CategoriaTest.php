<?php

namespace Tests\Feature;

use App\Models\Categoria;
use Tests\Concerns\CreatesAuthenticatedUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriaTest extends TestCase
{
    use RefreshDatabase, CreatesAuthenticatedUser;

    private string $token;
    private \App\Models\Usuario $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear usuario autenticado y obtener token
        $this->usuario = $this->createAuthenticatedUser();
        $this->token = $this->authenticateUser($this->usuario);
    }

    public function test_listar_categorias(): void
    {
        // Crear algunas categorías
        Categoria::create(['nombre' => 'Antibióticos', 'estado' => 'activo']);
        Categoria::create(['nombre' => 'Analgésicos', 'estado' => 'activo']);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/categorias');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'nombre', 'estado', 'created_at'],
                ],
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_crear_categoria(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/categorias', [
                'nombre' => 'Vitaminas',
                'descripcion' => 'Suplementos vitamínicos',
                'estado' => 'activo',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'nombre' => 'Vitaminas',
                    'descripcion' => 'Suplementos vitamínicos',
                    'estado' => 'activo',
                ],
            ]);

        $this->assertDatabaseHas('categorias', ['nombre' => 'Vitaminas']);
    }

    public function test_actualizar_categoria(): void
    {
        $categoria = Categoria::create(['nombre' => 'Original', 'estado' => 'activo']);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/categorias/{$categoria->id}", [
                'nombre' => 'Actualizada',
                'estado' => 'inactivo',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'nombre' => 'Actualizada',
                    'estado' => 'inactivo',
                ],
            ]);

        $this->assertDatabaseHas('categorias', ['nombre' => 'Actualizada']);
    }

    public function test_eliminar_categoria(): void
    {
        $categoria = Categoria::create(['nombre' => 'A Eliminar', 'estado' => 'activo']);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/categorias/{$categoria->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('categorias', ['id' => $categoria->id]);
    }

    public function test_toggle_estado_categoria(): void
    {
        $categoria = Categoria::create(['nombre' => 'Test', 'estado' => 'activo']);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->patchJson("/api/categorias/{$categoria->id}/toggle-estado");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['estado' => 'inactivo'],
            ]);
    }

    public function test_crear_categoria_duplicada_falla(): void
    {
        Categoria::create(['nombre' => 'Duplicada', 'estado' => 'activo']);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/categorias', [
                'nombre' => 'Duplicada',
                'estado' => 'activo',
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_categoria_no_encontrada(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/categorias/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404)
            ->assertJson(['success' => false]);
    }
}
