<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Lote;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\Usuario;
use App\Models\Venta;
use App\Models\VentaProducto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    private Usuario $adminUser;

    private Sucursal $sucursal;

    private Producto $producto;

    private Categoria $categoria;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

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

        $this->sucursal = Sucursal::create([
            'nombre' => 'Sucursal Test',
            'direccion' => 'Calle Test 123',
            'ciudad' => 'La Paz',
            'pais' => 'Bolivia',
            'telefono' => '70000001',
            'email' => 'sucursal@test.com',
            'estado' => 'activo',
        ]);

        $this->adminUser->update(['sucursal_id' => $this->sucursal->id]);

        $this->categoria = Categoria::create([
            'nombre' => 'Medicamentos',
            'descripcion' => 'Categoría de medicamentos',
            'estado' => 'activo',
        ]);

        $this->producto = Producto::create([
            'nombre' => 'Paracetamol 500mg',
            'descripcion' => 'Analgésico',
            'codigo_barras' => '1234567890123',
            'precio' => 10.00,
            'stock' => 100,
            'stock_actual' => 100,
            'stock_minimo' => 10,
            'categoria_id' => $this->categoria->id,
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

    private function crearVenta(float $total, int $cantidad = 2): Venta
    {
        $caja = Caja::create([
            'nombre' => 'Caja Principal',
            'numero_caja' => 1,
            'sucursal_id' => $this->sucursal->id,
            'estado' => 'abierta',
        ]);

        $cliente = Cliente::create([
            'ci' => '9999999',
            'nombre' => 'Cliente',
            'apellidos' => 'Test',
            'telefono' => '70000002',
            'estado' => 'activo',
        ]);

        $venta = Venta::create([
            'numero_venta' => 'VNT-DASH-'.uniqid(),
            'subtotal' => $total,
            'descuento' => 0,
            'impuestos' => 0,
            'total' => $total,
            'pagado' => $total,
            'saldo_pendiente' => 0,
            'tipo_pago' => 'contado',
            'metodo_pago' => 'efectivo',
            'estado' => 'completada',
            'cliente_id' => $cliente->id,
            'usuario_id' => $this->adminUser->id,
            'sucursal_id' => $this->sucursal->id,
            'caja_id' => $caja->id,
            'fecha_venta' => now(),
        ]);

        VentaProducto::create([
            'venta_id' => $venta->id,
            'producto_id' => $this->producto->id,
            'cantidad' => $cantidad,
            'precio_unitario' => 10,
            'descuento' => 0,
            'subtotal' => $cantidad * 10,
        ]);

        return $venta;
    }

    private function crearCompra(float $total): Compra
    {
        return Compra::create([
            'numero_compra' => 'CMP-DASH-'.uniqid(),
            'subtotal' => $total,
            'descuento' => 0,
            'impuestos' => 0,
            'total' => $total,
            'pagado' => $total,
            'saldo_pendiente' => 0,
            'estado' => 'completada',
            'metodo_pago' => 'efectivo',
            'tipo_documento' => 'factura',
            'usuario_id' => $this->adminUser->id,
            'sucursal_id' => $this->sucursal->id,
            'fecha_compra' => now(),
        ]);
    }

    public function test_puede_obtener_metricas_con_datos_reales(): void
    {
        Lote::create([
            'producto_id' => $this->producto->id,
            'numero_lote' => 'LOT-DASH',
            'fecha_vencimiento' => now()->addMonths(6),
            'stock' => 50,
            'stock_comprometido' => 0,
            'stock_minimo' => 2,
            'precio_costo' => 5.00,
            'precio_costo_promedio' => 5.00,
            'estado' => 'disponible',
        ]);

        $this->crearVenta(200.00);
        $this->crearCompra(100.00);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/dashboard/metrics?periodo=hoy')
            ->assertStatus(200)
            ->assertJsonPath('data.ventas.cantidad', 1)
            ->assertJsonPath('data.ventas.total', 200)
            ->assertJsonPath('data.compras.cantidad', 1)
            ->assertJsonPath('data.compras.total', 100)
            ->assertJsonPath('data.inventario.total_lotes', 1)
            ->assertJsonPath('data.inventario.stock_total', 50)
            ->assertJsonPath('data.productos.total', 1)
            ->assertJsonPath('data.productos.con_stock', 1)
            ->assertJsonPath('data.productos.sin_stock', 0);
    }

    public function test_puede_obtener_top_productos(): void
    {
        $this->crearVenta(20.00, 5);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/dashboard/top-productos?tipo=mas_vendidos&limite=10')
            ->assertStatus(200)
            ->assertJsonPath('data.0.nombre', $this->producto->nombre)
            ->assertJsonPath('data.0.total_vendido', 5);
    }

    public function test_puede_obtener_ventas_por_categoria(): void
    {
        $this->crearVenta(40.00, 4);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/dashboard/ventas-por-categoria')
            ->assertStatus(200)
            ->assertJsonPath('data.0.categoria', $this->categoria->nombre)
            ->assertJsonPath('data.0.transacciones', 1);
    }

    public function test_puede_obtener_actividad_reciente(): void
    {
        $this->crearVenta(20.00);
        $this->crearCompra(50.00);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/dashboard/actividad-reciente')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['tipo' => 'venta'])
            ->assertJsonFragment(['tipo' => 'compra']);
    }

    public function test_puede_obtener_comparativo_de_ventas(): void
    {
        $this->crearVenta(300.00);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/dashboard/comparativo')
            ->assertStatus(200)
            ->assertJsonPath('data.mes_actual', 300)
            ->assertJsonPath('data.mes_anterior', 0)
            ->assertJsonPath('data.tipo', 'subio');
    }

    public function test_metricas_incluyen_clientes_proveedores_por_estado_y_crecimientos(): void
    {
        Cliente::create([
            'ci' => '8888888',
            'nombre' => 'Cliente',
            'apellidos' => 'Dashboard',
            'telefono' => '70000002',
            'estado' => 'activo',
        ]);

        Proveedor::create([
            'nombre' => 'Laboratorios Andinos',
            'nit' => '1234567',
            'telefono' => '70000003',
            'email' => 'proveedor@test.com',
            'estado' => 'activo',
        ]);

        Compra::create([
            'numero_compra' => 'CMP-DASH-PEND-'.uniqid(),
            'subtotal' => 50,
            'descuento' => 0,
            'impuestos' => 0,
            'total' => 50,
            'pagado' => 0,
            'saldo_pendiente' => 50,
            'estado' => 'pendiente',
            'metodo_pago' => 'efectivo',
            'tipo_documento' => 'factura',
            'usuario_id' => $this->adminUser->id,
            'sucursal_id' => $this->sucursal->id,
            'fecha_compra' => now(),
        ]);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/dashboard/metrics?periodo=hoy')
            ->assertStatus(200)
            ->assertJsonPath('data.clientes.total', 1)
            ->assertJsonPath('data.clientes.activos', 1)
            ->assertJsonPath('data.clientes.nuevos', 1)
            ->assertJsonPath('data.clientes.crecimiento', 0)
            ->assertJsonPath('data.proveedores.total', 1)
            ->assertJsonPath('data.proveedores.activos', 1)
            ->assertJsonPath('data.proveedores.nuevos', 1)
            ->assertJsonPath('data.productos.crecimiento', 0)
            ->assertJsonPath('data.ventas.crecimiento', 0)
            ->assertJsonPath('data.compras.por_estado.pendiente', 1)
            ->assertJsonPath('data.compras.por_estado.recibida', 0);
    }

    public function test_puede_obtener_alertas_de_inventario(): void
    {
        Producto::create([
            'nombre' => 'Paracetamol Stock Bajo',
            'descripcion' => 'Alerta stock',
            'codigo_barras' => '1234567890999',
            'precio' => 10.00,
            'stock' => 3,
            'stock_actual' => 3,
            'stock_minimo' => 10,
            'lote' => 'L-AL-001',
            'categoria_id' => $this->categoria->id,
            'estado' => 'activo',
        ]);

        Producto::create([
            'nombre' => 'Omeprazol Por Vencer',
            'descripcion' => 'Alerta vencimiento',
            'codigo_barras' => '1234567890888',
            'precio' => 5.00,
            'stock' => 50,
            'stock_actual' => 50,
            'stock_minimo' => 5,
            'lote' => 'L-AL-002',
            'fecha_vencimiento' => now()->addDays(10),
            'categoria_id' => $this->categoria->id,
            'estado' => 'activo',
        ]);

        Producto::create([
            'nombre' => 'Captopril Vencido',
            'descripcion' => 'Alerta vencido',
            'codigo_barras' => '1234567890777',
            'precio' => 8.00,
            'stock' => 12,
            'stock_actual' => 12,
            'stock_minimo' => 5,
            'lote' => 'L-AL-003',
            'fecha_vencimiento' => now()->subDays(5),
            'categoria_id' => $this->categoria->id,
            'estado' => 'activo',
        ]);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/dashboard/alertas-inventario')
            ->assertStatus(200)
            ->assertJsonPath('data.bajo_stock.0.nombre', 'Paracetamol Stock Bajo')
            ->assertJsonPath('data.bajo_stock.0.stock', 3)
            ->assertJsonPath('data.bajo_stock.0.stock_minimo', 10)
            ->assertJsonPath('data.proximos_vencer.0.nombre', 'Omeprazol Por Vencer')
            ->assertJsonPath('data.vencidos.0.nombre', 'Captopril Vencido');
    }
}