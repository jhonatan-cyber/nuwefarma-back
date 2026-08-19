<?php

namespace Tests\Feature;

use App\Enums\CondicionVentaEnum;
use App\Models\Categoria;
use App\Models\Compra;
use App\Models\CompraProducto;
use App\Models\Lote;
use App\Models\MovimientoLote;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrdenCompraTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    private Usuario $usuario;

    private Proveedor $proveedor;

    private Producto $producto;

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

        $this->proveedor = Proveedor::create([
            'nombre' => 'Distribuidora Farma',
            'nit' => '10203040506',
            'telefono' => '70000000',
            'email' => 'ventas@farma.com',
            'estado' => 'activo',
        ]);

        $categoria = Categoria::create([
            'nombre' => 'Medicamentos',
            'descripcion' => 'Categoría',
            'estado' => 'activo',
        ]);

        $this->producto = Producto::create([
            'nombre' => 'Paracetamol 500mg',
            'codigo_barras' => '5000000000001',
            'categoria_id' => $categoria->id,
            'condicion_venta' => CondicionVentaEnum::VENTA_LIBRE,
            'stock_actual' => 4,
            'stock_minimo' => 10,
            'stock_maximo' => 100,
            'precio_venta' => 20.00,
            'precio_compra' => 8.00,
            'impuesto' => 0,
            'estado' => 'activo',
        ]);
    }

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    private function crearSolicitud(): string
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/ordenes-compra', [
                'tipo' => 'solicitud',
                'prioridad' => 'alta',
                'proveedor_id' => $this->proveedor->id,
                'notas' => 'Reposición de paracetamol',
                'productos' => [
                    [
                        'producto_id' => $this->producto->id,
                        'cantidad' => 50,
                        'precio_unitario' => 7.50,
                    ],
                ],
            ])
            ->assertStatus(201);

        return $response->json('data.id');
    }

    public function test_crea_solicitud_pendiente_de_aprobacion(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/ordenes-compra', [
                'tipo' => 'solicitud',
                'proveedor_id' => $this->proveedor->id,
                'productos' => [
                    ['producto_id' => $this->producto->id, 'cantidad' => 30, 'precio_unitario' => 7.50],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.tipo', 'solicitud')
            ->assertJsonPath('data.estado', 'pendiente_aprobacion')
            ->assertJsonPath('data.total', 225);

        $this->assertDatabaseHas('ordenes_compra', [
            'numero_orden' => $response->json('data.numero_orden'),
            'estado' => 'pendiente_aprobacion',
        ]);

        $this->assertDatabaseHas('orden_compra_productos', [
            'orden_id' => $response->json('data.id'),
            'producto_id' => $this->producto->id,
            'cantidad' => '30.00',
            'precio_unitario' => '7.5000',
        ]);
    }

    public function test_flujo_completo_hasta_recepcion(): void
    {
        $id = $this->crearSolicitud();
        $orden = OrdenCompra::findOrFail($id);

        // Aprobar
        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/ordenes-compra/{$id}/aprobar", ['notas' => 'OK'])
            ->assertStatus(200)
            ->assertJsonPath('data.estado', 'aprobada');

        // Enviar
        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/ordenes-compra/{$id}/enviar")
            ->assertStatus(200)
            ->assertJsonPath('data.estado', 'enviada');

        // Recibir
        $linea = $orden->productos()->first();

        $recibida = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/ordenes-compra/{$id}/recibir", [
                'items' => [
                    ['orden_producto_id' => $linea->id, 'cantidad' => 50],
                ],
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.estado', 'recibida');

        // Se generó la compra sin duplicar captura.
        $compra = Compra::where('numero_compra', $recibida->json('data.numero_orden'))->first();
        $this->assertNotNull($compra);
        $this->assertDatabaseHas('compra_productos', [
            'compra_id' => $compra->id,
            'producto_id' => $this->producto->id,
            'cantidad' => 50,
        ]);

        // Lote y kardex generados.
        $this->assertDatabaseHas('lotes', [
            'producto_id' => $this->producto->id,
            'stock' => 50,
        ]);

        $this->assertDatabaseHas('movimientos_lote', [
            'tipo_movimiento' => MovimientoLote::ENTRADA_COMPRA,
            'documento_id' => $compra->id,
            'cantidad' => 50,
        ]);

        $this->assertDatabaseHas('productos', ['id' => $this->producto->id, 'stock_actual' => 54]);

        $this->assertDatabaseHas('ordenes_compra', [
            'id' => $id,
            'estado' => 'recibida',
        ]);
    }

    public function test_recepcion_parcial_con_estado_enviada(): void
    {
        $id = $this->crearSolicitud();
        $orden = OrdenCompra::findOrFail($id);

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/ordenes-compra/{$id}/aprobar")
            ->assertStatus(200);

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/ordenes-compra/{$id}/enviar")
            ->assertStatus(200);

        $linea = $orden->productos()->first();

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/ordenes-compra/{$id}/recibir", [
                'items' => [
                    ['orden_producto_id' => $linea->id, 'cantidad' => 20],
                ],
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.estado', 'enviada')
            ->assertJsonPath('data.productos.0.cantidad_recibida', 20)
            ->assertJsonPath('data.productos.0.pendiente', 30);

        // Segunda recepción completa la orden.
        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/ordenes-compra/{$id}/recibir", [
                'items' => [
                    ['orden_producto_id' => $linea->id, 'cantidad' => 30],
                ],
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.estado', 'recibida');

        $this->assertDatabaseHas('lotes', ['producto_id' => $this->producto->id, 'stock' => 20]);
        $this->assertDatabaseHas('lotes', ['producto_id' => $this->producto->id, 'stock' => 30]);

        // Una sola compra generada, con dos líneas de recepción.
        $this->assertEquals(1, Compra::where('numero_compra', OrdenCompra::find($id)->numero_orden)->count());
    }

    public function test_rechaza_solicitud(): void
    {
        $id = $this->crearSolicitud();

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/ordenes-compra/{$id}/rechazar", ['motivo' => 'Presupuesto insuficiente'])
            ->assertStatus(200)
            ->assertJsonPath('data.estado', 'rechazada')
            ->assertJsonPath('data.motivo_rechazo', 'Presupuesto insuficiente');
    }

    public function test_cancela_orden_pendiente(): void
    {
        $id = $this->crearSolicitud();

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/ordenes-compra/{$id}/cancelar", ['motivo' => 'Ya no se necesita'])
            ->assertStatus(200)
            ->assertJsonPath('data.estado', 'cancelada');

        $this->assertDatabaseMissing('lotes', ['producto_id' => $this->producto->id]);
    }

    public function test_sugiere_reposicion_por_stock_minimo(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/ordenes-compra/sugerencias/reposicion');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $sugerencias = collect($response->json('data.sugerencias'));
        $esta = $sugerencias->firstWhere('producto_id', $this->producto->id);
        $this->assertNotNull($esta, 'Debe sugerir el producto con stock bajo');
        $this->assertGreaterThan(0, $esta['cantidad_sugerida']);
    }

    public function test_historial_de_precios_y_comparativa_por_proveedor(): void
    {
        $id = $this->crearSolicitud();
        $orden = OrdenCompra::findOrFail($id);

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/ordenes-compra/{$id}/aprobar")
            ->assertStatus(200);
        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/ordenes-compra/{$id}/enviar")
            ->assertStatus(200);

        $linea = $orden->productos()->first();
        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/ordenes-compra/{$id}/recibir", [
                'items' => [
                    ['orden_producto_id' => $linea->id, 'cantidad' => 50],
                ],
            ])
            ->assertStatus(200);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/ordenes-compra/historial-precios/'.$this->producto->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.producto.id', $this->producto->id)
            ->assertJsonPath('data.historial.0.precio_unitario', 7.5)
            ->assertJsonPath('data.comparativa_por_proveedor.0.proveedor.id', $this->proveedor->id)
            ->assertJsonPath('data.comparativa_por_proveedor.0.mejor_precio', 7.5);
    }
}