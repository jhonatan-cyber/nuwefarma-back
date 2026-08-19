<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Lote;
use App\Models\MovimientoLote;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\Usuario;
use App\Models\Venta;
use App\Models\VentaProducto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoteTrazabilidadTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    private Usuario $adminUser;

    private Sucursal $sucursal;

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

        $categoria = Categoria::create([
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
            'stock_minimo' => 10,
            'categoria_id' => $categoria->id,
            'estado' => 'activo',
        ]);
        $this->producto->update(['stock_actual' => 10]);

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

    private function crearLote(int $stock, string $numero = 'LOT-001'): Lote
    {
        return Lote::create([
            'producto_id' => $this->producto->id,
            'numero_lote' => $numero,
            'fecha_vencimiento' => now()->addMonths(6),
            'stock' => $stock,
            'stock_comprometido' => 0,
            'stock_minimo' => 2,
            'stock_maximo' => 100,
            'precio_costo' => 5.00,
            'precio_costo_promedio' => 5.00,
            'estado' => 'disponible',
        ]);
    }

    private function crearVentaCompletadaConLote(Lote $lote, int $cantidad): Venta
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
            'numero_venta' => 'VNT-LOTE-001',
            'subtotal' => 20.00,
            'descuento' => 0,
            'impuestos' => 0,
            'total' => 20.00,
            'pagado' => 20.00,
            'saldo_pendiente' => 0,
            'tipo_pago' => 'contado',
            'metodo_pago' => 'efectivo',
            'estado' => 'pendiente',
            'cliente_id' => $cliente->id,
            'usuario_id' => $this->adminUser->id,
            'sucursal_id' => $this->sucursal->id,
            'caja_id' => $caja->id,
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

    public function test_venta_completada_con_lotes_descuenta_fefo_registra_movimiento_y_lote_id(): void
    {
        $lote = $this->crearLote(10);

        $venta = $this->crearVentaCompletadaConLote($lote, 2);

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/ventas/{$venta->id}/completar")
            ->assertStatus(200)
            ->assertJsonPath('data.attributes.estado', 'completada');

        $this->assertSame(8, $lote->fresh()->stock);
        $this->assertSame(8, $this->producto->fresh()->stock_actual);

        $item = $venta->ventaProductos()->first();
        $this->assertSame($lote->id, $item->lote_id);

        $this->assertDatabaseHas('movimientos_lote', [
            'lote_id' => $lote->id,
            'tipo_movimiento' => MovimientoLote::SALIDA_VENTA,
            'cantidad' => 2,
            'documento_tipo' => 'Venta',
            'documento_id' => $venta->id,
        ]);
    }

    public function test_devolver_venta_con_lotes_restaura_stock_desde_movimientos(): void
    {
        $lote = $this->crearLote(10);

        $venta = $this->crearVentaCompletadaConLote($lote, 2);

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/ventas/{$venta->id}/completar")
            ->assertStatus(200);

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/ventas/{$venta->id}/devolver")
            ->assertStatus(200)
            ->assertJsonPath('data.attributes.estado', 'devuelta');

        $this->assertSame(10, $this->producto->fresh()->stock_actual);
        $this->assertSame(10, $lote->fresh()->stock);
        $this->assertSame('disponible', $lote->fresh()->estado);

        $this->assertDatabaseHas('movimientos_lote', [
            'lote_id' => $lote->id,
            'tipo_movimiento' => MovimientoLote::ENTRADA_DEVOLUCION,
            'cantidad' => 2,
            'documento_id' => $venta->id,
        ]);
    }

    public function test_actualizar_stock_de_lote_genera_movimiento_de_ajuste(): void
    {
        $creacion = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/lotes', [
                'producto_id' => $this->producto->id,
                'numero_lote' => 'LOT-AJUSTE',
                'fecha_vencimiento' => now()->addMonths(6)->format('Y-m-d'),
                'stock' => 10,
                'stock_minimo' => 2,
                'precio_costo' => 5.00,
            ]);

        $creacion->assertStatus(201);
        $loteId = $creacion['data']['id'];

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/lotes/{$loteId}", ['stock' => 15])
            ->assertStatus(200);

        $this->assertSame(15, Lote::find($loteId)->stock);

        $this->assertDatabaseHas('movimientos_lote', [
            'lote_id' => $loteId,
            'tipo_movimiento' => MovimientoLote::AJUSTE_POSITIVO,
            'cantidad' => 5,
            'documento_tipo' => 'AjusteInventario',
        ]);

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/lotes/{$loteId}", ['stock' => 3])
            ->assertStatus(200);

        $this->assertSame(3, Lote::find($loteId)->stock);

        $this->assertDatabaseHas('movimientos_lote', [
            'lote_id' => $loteId,
            'tipo_movimiento' => MovimientoLote::AJUSTE_NEGATIVO,
            'cantidad' => 12,
            'documento_tipo' => 'AjusteInventario',
        ]);
    }

    public function test_no_puede_cambiar_numero_de_lote_con_movimientos_registrados(): void
    {
        $creacion = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/lotes', [
                'producto_id' => $this->producto->id,
                'numero_lote' => 'LOT-INMUTABLE',
                'fecha_vencimiento' => now()->addMonths(6)->format('Y-m-d'),
                'stock' => 10,
                'stock_minimo' => 2,
                'precio_costo' => 5.00,
            ]);

        $creacion->assertStatus(201);
        $loteId = $creacion['data']['id'];

        $this->assertDatabaseHas('movimientos_lote', [
            'lote_id' => $loteId,
            'tipo_movimiento' => MovimientoLote::ENTRADA_COMPRA,
        ]);

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/lotes/{$loteId}", ['numero_lote' => 'LOT-CAMBIADO'])
            ->assertStatus(400);

        $this->assertSame('LOT-INMUTABLE', Lote::find($loteId)->numero_lote);
    }

    public function test_traslado_enviado_no_puede_duplicar_procesamiento(): void
    {
        $lote = $this->crearLote(10);

        $sucursalDestino = Sucursal::create([
            'nombre' => 'Sucursal Destino',
            'direccion' => 'Calle Destino 1',
            'ciudad' => 'El Alto',
            'pais' => 'Bolivia',
            'telefono' => '70000003',
            'email' => 'destino@test.com',
            'estado' => 'activo',
        ]);

        $created = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/traslados', [
                'sucursal_destino_id' => $sucursalDestino->id,
                'items' => [
                    ['lote_origen_id' => $lote->id, 'cantidad' => 3],
                ],
            ]);

        $created->assertStatus(201);
        $trasladoId = $created['data']['id'];

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/traslados/{$trasladoId}/enviar")
            ->assertStatus(200);

        $this->assertSame(7, $lote->fresh()->stock);
        $this->assertSame(1, MovimientoLote::where('documento_id', $trasladoId)
            ->where('tipo_movimiento', MovimientoLote::SALIDA_TRASLADO_OUT)
            ->count());

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/traslados/{$trasladoId}/enviar")
            ->assertStatus(422);

        $this->assertSame(7, $lote->fresh()->stock);
        $this->assertSame(1, MovimientoLote::where('documento_id', $trasladoId)
            ->where('tipo_movimiento', MovimientoLote::SALIDA_TRASLADO_OUT)
            ->count());
    }

    public function test_ajuste_decremento_aplica_y_registra_movimiento(): void
    {
        $lote = $this->crearLote(2);

        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/ajustes-inventario', [
                'tipo' => 'decremento',
                'motivo' => 'merma',
                'items' => [
                    ['lote_id' => $lote->id, 'stock_nuevo' => 0],
                ],
            ])
            ->assertStatus(201);

        $this->assertSame(0, $lote->fresh()->stock);
        $this->assertSame('agotado', $lote->fresh()->estado);

        $this->assertDatabaseHas('movimientos_lote', [
            'lote_id' => $lote->id,
            'tipo_movimiento' => MovimientoLote::AJUSTE_NEGATIVO,
            'cantidad' => 2,
            'documento_tipo' => 'AjusteInventario',
        ]);
    }

    public function test_ajuste_no_permiten_stock_objetivo_negativo(): void
    {
        $lote = $this->crearLote(2);

        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/ajustes-inventario', [
                'tipo' => 'decremento',
                'motivo' => 'merma',
                'items' => [
                    ['lote_id' => $lote->id, 'stock_nuevo' => -5],
                ],
            ])
            ->assertStatus(422);

        $this->assertSame(2, $lote->fresh()->stock);
        $this->assertDatabaseCount('ajustes_inventario', 0);
    }
}