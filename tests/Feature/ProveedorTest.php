<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProveedorTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    private Usuario $usuario;

    private Sucursal $sucursal;

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

        $this->sucursal = Sucursal::create([
            'nombre' => 'Sucursal Test',
            'direccion' => 'Calle Test 123',
            'ciudad' => 'La Paz',
            'pais' => 'Bolivia',
            'telefono' => '70000001',
            'email' => 'sucursal@test.com',
            'estado' => 'activo',
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $this->token = $loginResponse['data']['token'];
    }

    public function test_puede_toggle_estado_proveedor(): void
    {
        $proveedor = Proveedor::create([
            'nombre' => 'Laboratorio SA',
            'nit' => '123456789',
            'estado' => 'activo',
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$this->token])
            ->patchJson("/api/v1/proveedores/{$proveedor->id}/toggle-estado");

        $response->assertStatus(200)
            ->assertJsonPath('data.estado', 'inactivo');

        $this->assertSame('inactivo', $proveedor->fresh()->estado);

        $this->withHeaders(['Authorization' => 'Bearer '.$this->token])
            ->patchJson("/api/v1/proveedores/{$proveedor->id}/toggle-estado")
            ->assertJsonPath('data.estado', 'activo');
    }

    public function test_show_proveedor_incluye_conteos_relacionados(): void
    {
        $proveedor = Proveedor::create([
            'nombre' => 'Distribuidora Andina',
            'nit' => '987654321',
            'estado' => 'activo',
        ]);

        $categoria = Categoria::create([
            'nombre' => 'Medicamentos',
            'descripcion' => 'Categoría de medicamentos',
            'estado' => 'activo',
        ]);

        Producto::create([
            'nombre' => 'Paracetamol',
            'categoria_id' => $categoria->id,
            'proveedor_id' => $proveedor->id,
            'precio_compra' => 5,
            'precio_venta' => 10,
            'stock_actual' => 10,
            'stock_minimo' => 2,
            'estado' => 'activo',
        ]);

        Producto::create([
            'nombre' => 'Ibuprofeno',
            'categoria_id' => $categoria->id,
            'proveedor_id' => $proveedor->id,
            'precio_compra' => 8,
            'precio_venta' => 15,
            'stock_actual' => 5,
            'stock_minimo' => 2,
            'estado' => 'activo',
        ]);

        Compra::create([
            'numero_compra' => 'CMP-TEST-1',
            'subtotal' => 100,
            'descuento' => 0,
            'impuestos' => 0,
            'total' => 100,
            'estado' => 'recibida',
            'metodo_pago' => 'efectivo',
            'proveedor_id' => $proveedor->id,
            'usuario_id' => $this->usuario->id,
            'sucursal_id' => $this->sucursal->id,
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$this->token])
            ->getJson("/api/v1/proveedores/{$proveedor->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.productos_count', 2)
            ->assertJsonPath('data.compras_count', 1);
    }

    public function test_stats_overview_proveedores(): void
    {
        $proveedorActivo = Proveedor::create([
            'nombre' => 'Laboratorio Activo',
            'estado' => 'activo',
        ]);

        Proveedor::create([
            'nombre' => 'Laboratorio Inactivo',
            'estado' => 'inactivo',
        ]);

        $categoria = Categoria::create([
            'nombre' => 'Medicamentos',
            'descripcion' => 'Categoría',
            'estado' => 'activo',
        ]);

        Producto::create([
            'nombre' => 'Paracetamol',
            'categoria_id' => $categoria->id,
            'proveedor_id' => $proveedorActivo->id,
            'estado' => 'activo',
        ]);

        Compra::create([
            'numero_compra' => 'CMP-TEST-2',
            'subtotal' => 500,
            'descuento' => 0,
            'impuestos' => 0,
            'total' => 500,
            'estado' => 'recibida',
            'metodo_pago' => 'efectivo',
            'proveedor_id' => $proveedorActivo->id,
            'usuario_id' => $this->usuario->id,
            'sucursal_id' => $this->sucursal->id,
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$this->token])
            ->getJson('/api/v1/proveedores/stats/overview');

        $response->assertStatus(200)
            ->assertJsonPath('data.resumen.total', 2)
            ->assertJsonPath('data.resumen.activos', 1)
            ->assertJsonPath('data.resumen.inactivos', 1)
            ->assertJsonPath('data.resumen.con_productos', 1)
            ->assertJsonPath('data.compras.monto_recibido', 500);
    }
}