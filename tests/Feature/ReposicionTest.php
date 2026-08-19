<?php

namespace Tests\Feature;

use App\Enums\CondicionVentaEnum;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\Usuario;
use App\Models\Venta;
use App\Models\VentaProducto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReposicionTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

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
        $this->usuario->update(['sucursal_id' => $sucursal->id]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);
        $this->token = $login['data']['token'];
    }

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    private function crearProducto(
        string $nombre,
        int $stock,
        int $stockMinimo,
        int $stockMaximo,
        string $codigo,
    ): Producto {
        $categoria = Categoria::create([
            'nombre' => $nombre.'-categoria',
            'descripcion' => 'Categoría',
            'estado' => 'activo',
        ]);

        return Producto::create([
            'nombre' => $nombre,
            'codigo_barras' => $codigo,
            'categoria_id' => $categoria->id,
            'condicion_venta' => CondicionVentaEnum::VENTA_LIBRE,
            'stock_actual' => $stock,
            'stock_minimo' => $stockMinimo,
            'stock_maximo' => $stockMaximo,
            'precio_venta' => 25.00,
            'precio_compra' => 9.00,
            'impuesto' => 0,
            'estado' => 'activo',
        ]);
    }

    public function test_sugiere_reposicion_para_producto_bajo_stock(): void
    {
        $producto = $this->crearProducto('Ibuprofeno 400mg', stock: 5, stockMinimo: 20, stockMaximo: 100, codigo: '6000000000001');

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/ordenes-compra/sugerencias/reposicion')
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $sugerencias = collect($response->json('data.sugerencias'));

        $sugerida = $sugerencias->firstWhere('producto_id', $producto->id);
        $this->assertNotNull($sugerida);
        $this->assertSame(95, $sugerida['cantidad_sugerida']);
        $this->assertSame(5, $sugerida['stock_actual']);
    }

    public function test_no_sugiere_producto_con_stock_normal(): void
    {
        $producto = $this->crearProducto('Aspirina 100mg', stock: 80, stockMinimo: 10, stockMaximo: 100, codigo: '6000000000002');

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/ordenes-compra/sugerencias/reposicion')
            ->assertStatus(200);

        $sugerencias = collect($response->json('data.sugerencias'));
        $this->assertNull($sugerencias->firstWhere('producto_id', $producto->id));
    }

    public function test_sugiere_reposicion_por_rotacion_aunque_est_sobre_minimo(): void
    {
        // Stock sobre el mínimo pero con ventas recientes que agotarían el stock.
        $producto = $this->crearProducto('Amoxicilina 500mg', stock: 15, stockMinimo: 10, stockMaximo: 60, codigo: '6000000000003');

        // 60 unidades vendidas en los últimos 30 días => consumo diario de 2.
        $venta = Venta::create([
            'numero_venta' => 'V-00000001',
            'subtotal' => 1500,
            'descuento' => 0,
            'impuestos' => 0,
            'total' => 1500,
            'pagado' => 1500,
            'saldo_pendiente' => 0,
            'estado' => 'completada',
            'metodo_pago' => 'efectivo',
            'usuario_id' => $this->usuario->id,
            'fecha_venta' => now(),
        ]);

        VentaProducto::create([
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'cantidad' => 60,
            'precio_unitario' => 25,
            'descuento_unitario' => 0,
            'subtotal' => 1500,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/ordenes-compra/sugerencias/reposicion')
            ->assertStatus(200);

        $sugerencias = collect($response->json('data.sugerencias'));
        $sugerida = $sugerencias->firstWhere('producto_id', $producto->id);
        $this->assertNotNull($sugerida);
        $this->assertSame(60, $sugerida['ventas_30_dias']);
    }
}