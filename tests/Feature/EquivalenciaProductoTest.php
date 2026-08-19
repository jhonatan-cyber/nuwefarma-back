<?php

namespace Tests\Feature;

use App\Enums\CondicionVentaEnum;
use App\Models\Categoria;
use App\Models\EquivalenciaProducto;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EquivalenciaProductoTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    private Producto $paracetamol;

    private Producto $paracetamolGenerico;

    private Producto $ibuprofeno;

    protected function setUp(): void
    {
        parent::setUp();

        $rol = Rol::create([
            'nombre' => 'Administrador',
            'descripcion' => 'Acceso completo',
            'permiso_id' => [],
            'estado' => 'activo',
        ]);

        $usuario = Usuario::create([
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

        $sucursal = Sucursal::create([
            'nombre' => 'Sucursal Test',
            'direccion' => 'Calle Test 123',
            'ciudad' => 'La Paz',
            'pais' => 'Bolivia',
            'telefono' => '70000001',
            'email' => 'sucursal@test.com',
            'estado' => 'activo',
        ]);
        $usuario->update(['sucursal_id' => $sucursal->id]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);
        $this->token = $login['data']['token'];

        $categoria = Categoria::first() ?? Categoria::create([
            'nombre' => 'Medicamentos',
            'descripcion' => 'Categoría',
            'estado' => 'activo',
        ]);

        $this->paracetamol = $this->crearProducto('Panadol 500mg', '2000000000001', 'Paracetamol', $categoria);
        $this->paracetamolGenerico = $this->crearProducto('Paracetamol Genérico 500mg', '2000000000002', 'Paracetamol', $categoria);
        $this->ibuprofeno = $this->crearProducto('Ibuprofeno 400mg', '2000000000003', 'Ibuprofeno', $categoria);
    }

    private function crearProducto(string $nombre, string $codigo, string $principio, Categoria $categoria): Producto
    {
        return Producto::create([
            'nombre' => $nombre,
            'codigo_barras' => $codigo,
            'categoria_id' => $categoria->id,
            'principio_activo' => $principio,
            'condicion_venta' => CondicionVentaEnum::VENTA_LIBRE,
            'laboratorio' => 'Laboratorio Test',
            'stock_actual' => 50,
            'stock_minimo' => 5,
            'precio_venta' => 30.00,
            'precio_compra' => 12.00,
            'impuesto' => 0,
            'estado' => 'activo',
        ]);
    }

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    public function test_puede_registrar_una_equivalencia(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/equivalencias', [
                'producto_id' => $this->paracetamol->id,
                'producto_equivalente_id' => $this->paracetamolGenerico->id,
                'tipo' => 'generico',
                'factor_conversion' => 1,
                'notas' => 'Misma dosis',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tipo', 'generico')
            ->assertJsonPath('data.producto.nombre', $this->paracetamol->nombre)
            ->assertJsonPath('data.producto_equivalente.nombre', $this->paracetamolGenerico->nombre);

        $this->assertDatabaseHas('equivalencia_productos', [
            'producto_id' => $this->paracetamol->id,
            'producto_equivalente_id' => $this->paracetamolGenerico->id,
        ]);
    }

    public function test_no_permite_auto_referencia_ni_duplicados(): void
    {
        // Auto-referencia
        $auto = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/equivalencias', [
                'producto_id' => $this->paracetamol->id,
                'producto_equivalente_id' => $this->paracetamol->id,
            ]);
        $auto->assertStatus(422);

        // Crear relación válida
        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/equivalencias', [
                'producto_id' => $this->paracetamol->id,
                'producto_equivalente_id' => $this->paracetamolGenerico->id,
            ])
            ->assertStatus(201);

        // Duplicado en el mismo sentido
        $dup = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/equivalencias', [
                'producto_id' => $this->paracetamol->id,
                'producto_equivalente_id' => $this->paracetamolGenerico->id,
            ]);
        $dup->assertStatus(409);

        // Duplicado en sentido inverso
        $inverso = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/equivalencias', [
                'producto_id' => $this->paracetamolGenerico->id,
                'producto_equivalente_id' => $this->paracetamol->id,
            ]);
        $inverso->assertStatus(409);
    }

    public function test_sugiere_productos_por_principio_activo(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/equivalencias/sugeridos/{$this->paracetamol->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.producto.id', $this->paracetamol->id);

        $ids = collect($response->json('data.sugeridos'))->pluck('id')->all();
        $this->assertContains($this->paracetamolGenerico->id, $ids);
        $this->assertNotContains($this->ibuprofeno->id, $ids);
    }

    public function test_sugeridos_excluyen_ya_vinculados(): void
    {
        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/equivalencias', [
                'producto_id' => $this->paracetamol->id,
                'producto_equivalente_id' => $this->paracetamolGenerico->id,
            ])
            ->assertStatus(201);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/equivalencias/sugeridos/{$this->paracetamol->id}");

        $ids = collect($response->json('data.sugeridos'))->pluck('id')->all();
        $this->assertNotContains($this->paracetamolGenerico->id, $ids);
    }

    public function test_puede_listar_y_eliminar_equivalencias(): void
    {
        $creada = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/equivalencias', [
                'producto_id' => $this->paracetamol->id,
                'producto_equivalente_id' => $this->paracetamolGenerico->id,
            ])
            ->assertStatus(201);
        $id = $creada->json('data.id');

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/equivalencias')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1);

        $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/equivalencias/{$id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('equivalencia_productos', ['id' => $id]);
    }
}