<?php

namespace Tests\Feature;

use App\Models\ArqueoCaja;
use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\MovimientoCaja;
use App\Models\MovimientoLote;
use App\Models\NotaCredito;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\Usuario;
use App\Models\Venta;
use App\Models\VentaProducto;
use App\Models\Compra;
use App\Models\CompraProducto;
use App\Models\Lote;
use App\Services\FinancialCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FinanzasOperativasTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    private Usuario $adminUser;

    private Sucursal $sucursal;

    private Caja $caja;

    private Cliente $cliente;

    private Proveedor $proveedor;

    private Producto $producto;

    private Categoria $categoria;

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

        $this->caja = Caja::create([
            'nombre' => 'Caja Principal',
            'numero_caja' => 1,
            'sucursal_id' => $this->sucursal->id,
            'saldo_actual' => 100,
            'estado' => 'abierta',
        ]);

        $this->cliente = Cliente::create([
            'ci' => '9999999',
            'nombre' => 'Cliente',
            'apellidos' => 'Test',
            'telefono' => '70000002',
            'estado' => 'activo',
        ]);

        $this->proveedor = Proveedor::create([
            'nombre' => 'Proveedor Test',
            'nit' => '123456789',
            'telefono' => '70000003',
            'email' => 'proveedor@test.com',
            'estado' => 'activo',
        ]);

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

    private function crearVentaCreditoCompletada(float $total, ?string $fechaVencimiento = null, int $sufijo = 0): Venta
    {
        $venta = Venta::create([
            'numero_venta' => 'VNT-CREDITO-'.(2000 + $sufijo),
            'subtotal' => $total,
            'descuento' => 0,
            'impuestos' => 0,
            'total' => $total,
            'pagado' => 0,
            'saldo_pendiente' => $total,
            'tipo_pago' => 'credito',
            'metodo_pago' => 'efectivo',
            'estado' => 'completada',
            'cliente_id' => $this->cliente->id,
            'usuario_id' => $this->adminUser->id,
            'sucursal_id' => $this->sucursal->id,
            'caja_id' => $this->caja->id,
            'fecha_vencimiento' => $fechaVencimiento,
        ]);

        VentaProducto::create([
            'venta_id' => $venta->id,
            'producto_id' => $this->producto->id,
            'cantidad' => 2,
            'precio_unitario' => 10,
            'descuento_unitario' => 0,
            'subtotal' => 20,
        ]);

        return $venta;
    }

    private function crearVentaPagadaCompletada(float $total, int $sufijo = 0): Venta
    {
        $venta = Venta::create([
            'numero_venta' => 'VNT-PAGADA-'.(3000 + $sufijo),
            'subtotal' => $total,
            'descuento' => 0,
            'impuestos' => 0,
            'total' => $total,
            'pagado' => $total,
            'saldo_pendiente' => 0,
            'tipo_pago' => 'contado',
            'metodo_pago' => 'efectivo',
            'estado' => 'completada',
            'cliente_id' => $this->cliente->id,
            'usuario_id' => $this->adminUser->id,
            'sucursal_id' => $this->sucursal->id,
            'caja_id' => $this->caja->id,
        ]);

        return $venta;
    }

    private function registrarSalidaDeLote(Venta $venta, float $costoTotal): void
    {
        $lote = Lote::create([
            'producto_id' => $this->producto->id,
            'numero_lote' => 'LOTE-TEST-'.$venta->id,
            'stock' => 90,
            'estado' => 'disponible',
            'precio_costo' => $costoTotal / 2,
            'fecha_vencimiento' => now()->addYear()->toDateString(),
            'usuario_id' => $this->adminUser->id,
            'sucursal_id' => $this->sucursal->id,
        ]);

        MovimientoLote::create([
            'lote_id' => $lote->id,
            'tipo_movimiento' => MovimientoLote::SALIDA_VENTA,
            'cantidad' => 2,
            'stock_anterior' => 92,
            'stock_nuevo' => 90,
            'documento_tipo' => 'Venta',
            'documento_id' => $venta->id,
            'documento_numero' => $venta->numero_venta,
            'costo_unitario' => $costoTotal / 2,
            'costo_total' => $costoTotal,
            'usuario_id' => $this->adminUser->id,
            'sucursal_id' => $this->sucursal->id,
        ]);
    }

    // ------------------------------------------------------------------
    // T2 — Cuentas por cobrar con vencimientos
    // ------------------------------------------------------------------

    public function test_cuentas_por_cobrar_incluyen_mora_y_resumen(): void
    {
        $this->crearVentaCreditoCompletada(100.00, now()->subDays(5)->toDateString(), 1);
        $this->crearVentaCreditoCompletada(50.00, now()->addDays(10)->toDateString(), 2);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/pagos/cuentas-por-cobrar');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'documento_numero',
                            'saldo_pendiente',
                            'fecha_vencimiento',
                            'dias_vencido',
                            'estado_mora',
                        ],
                    ],
                    'resumen' => [
                        'total_por_cobrar',
                        'cantidad_cuentas',
                        'cuentas_vencidas',
                        'monto_vencido',
                    ],
                ],
            ]);

        $estados = collect($response->json('data.data'))->pluck('estado_mora');

        $this->assertContains('vencido', $estados);
        $this->assertContains('al_dia', $estados);
        $this->assertSame(1, $response->json('data.resumen.cuentas_vencidas'));
        $this->assertSame(150.0, (float) $response->json('data.resumen.total_por_cobrar'));
        $this->assertSame(100.0, (float) $response->json('data.resumen.monto_vencido'));
    }

    public function test_cuentas_por_pagar_incluyen_mora_y_resumen(): void
    {
        $this->crearCompraVencida(80.00, now()->subDays(3)->toDateString());

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/pagos/cuentas-por-pagar');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'documento_numero',
                            'saldo_pendiente',
                            'estado_mora',
                        ],
                    ],
                    'resumen' => [
                        'total_por_pagar',
                        'cuentas_vencidas',
                        'monto_vencido',
                    ],
                ],
            ]);

        $this->assertSame('vencido', $response->json('data.data.0.estado_mora'));
        $this->assertSame(80.0, (float) $response->json('data.resumen.total_por_pagar'));
    }

    private function crearCompraVencida(float $total, ?string $fechaVencimiento = null): Compra
    {
        return Compra::create([
            'numero_compra' => 'CMP-VENCIDA-1',
            'proveedor_id' => $this->proveedor->id,
            'sucursal_id' => $this->sucursal->id,
            'usuario_id' => $this->adminUser->id,
            'metodo_pago' => 'efectivo',
            'subtotal' => $total,
            'descuento' => 0,
            'impuestos' => 0,
            'total' => $total,
            'pagado' => 0,
            'saldo_pendiente' => $total,
            'estado' => 'recibida',
            'caja_id' => $this->caja->id,
            'fecha_vencimiento' => $fechaVencimiento,
        ]);
    }

    // ------------------------------------------------------------------
    // T3 — Pagos de compras
    // ------------------------------------------------------------------

    public function test_puede_realizar_pago_total_de_compra(): void
    {
        $compra = $this->crearCompraVencida(42.00);

        $response = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/compras/{$compra->id}/pagar", [
                'monto' => 42.00,
                'metodo_pago' => 'efectivo',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.saldo_pendiente', '0.00')
            ->assertJsonPath('data.pagado', '42.00');

        $this->assertSame('0.00', $compra->fresh()->saldo_pendiente);
        $this->assertSame(1, Pago::where('documento_id', $compra->id)->count());
        $this->assertDatabaseHas('movimientos_caja', [
            'caja_id' => $this->caja->id,
            'tipo' => MovimientoCaja::EGRESO,
            'origen' => MovimientoCaja::ORIGEN_COMPRA,
            'documento_id' => $compra->id,
            'monto' => '42.00',
        ]);
        $this->assertSame('58.00', $this->caja->fresh()->saldo_actual);
    }

    public function test_no_puede_pagar_compra_con_caja_cerrada(): void
    {
        $compra = $this->crearCompraVencida(42.00);
        $this->caja->cerrar();

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/compras/{$compra->id}/pagar", [
                'monto' => 42.00,
            ])
            ->assertStatus(409);

        $this->assertSame('42.00', $compra->fresh()->saldo_pendiente);
        $this->assertSame(0, Pago::count());
    }

    // ------------------------------------------------------------------
    // T5 — Notas de crédito
    // ------------------------------------------------------------------

    public function test_devolucion_de_venta_pagada_emite_nota_credito(): void
    {
        $venta = $this->crearVentaPagadaCompletada(100.00);

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/ventas/{$venta->id}/devolver", [
                'motivo' => 'Cliente devolvió medicamento',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.attributes.estado', 'devuelta');

        $this->assertDatabaseHas('notas_credito', [
            'documento_tipo' => 'Venta',
            'documento_id' => $venta->id,
            'monto' => '100.00',
            'estado' => NotaCredito::ESTADO_EMITIDA,
        ]);
    }

    public function test_puede_aplicar_nota_credito_a_saldo_pendiente(): void
    {
        $nota = NotaCredito::create([
            'numero' => NotaCredito::generateNumero(),
            'documento_tipo' => 'Venta',
            'documento_id' => $this->crearVentaPagadaCompletada(100.00)->id,
            'documento_numero' => 'VNT-PAGADA',
            'monto' => 30.00,
            'estado' => NotaCredito::ESTADO_EMITIDA,
            'usuario_id' => $this->adminUser->id,
            'sucursal_id' => $this->sucursal->id,
        ]);

        $venta = $this->crearVentaCreditoCompletada(100.00, null, 9);

        $response = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/notas-credito/{$nota->id}/aplicar", [
                'documento_tipo' => 'Venta',
                'documento_id' => $venta->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.estado', 'aplicada');

        $this->assertSame('30.00', $venta->fresh()->pagado);
        $this->assertSame('70.00', $venta->fresh()->saldo_pendiente);
        $this->assertSame(NotaCredito::ESTADO_APLICADA, $nota->fresh()->estado);
    }

    public function test_no_puede_aplicar_nota_credito_anulada(): void
    {
        $nota = NotaCredito::create([
            'numero' => NotaCredito::generateNumero(),
            'documento_tipo' => 'Venta',
            'documento_id' => $this->crearVentaPagadaCompletada(100.00, 5)->id,
            'documento_numero' => 'VNT-PAGADA',
            'monto' => 30.00,
            'estado' => NotaCredito::ESTADO_ANULADA,
            'usuario_id' => $this->adminUser->id,
            'sucursal_id' => $this->sucursal->id,
        ]);

        $venta = $this->crearVentaCreditoCompletada(100.00, null, 10);

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/notas-credito/{$nota->id}/aplicar", [
                'documento_tipo' => 'Venta',
                'documento_id' => $venta->id,
            ])
            ->assertStatus(409);

        $this->assertSame('0.00', $venta->fresh()->pagado);
        $this->assertSame('100.00', $venta->fresh()->saldo_pendiente);
    }

    public function test_no_puede_aplicar_nota_credito_a_documento_de_otro_tipo(): void
    {
        $nota = NotaCredito::create([
            'numero' => NotaCredito::generateNumero(),
            'documento_tipo' => 'Compra',
            'documento_id' => $this->crearCompraVencida(80.00)->id,
            'documento_numero' => 'CMP-1',
            'monto' => 30.00,
            'estado' => NotaCredito::ESTADO_EMITIDA,
            'usuario_id' => $this->adminUser->id,
            'sucursal_id' => $this->sucursal->id,
        ]);

        $venta = $this->crearVentaCreditoCompletada(100.00, null, 11);

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/notas-credito/{$nota->id}/aplicar", [
                'documento_tipo' => 'Venta',
                'documento_id' => $venta->id,
            ])
            ->assertStatus(409);
    }

    // ------------------------------------------------------------------
    // T4 — Arqueo y conciliación
    // ------------------------------------------------------------------

    public function test_puede_realizar_y_conciliar_arqueo(): void
    {
        $this->caja->forceFill(['saldo_actual' => 500])->save();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/cajas/{$this->caja->id}/arqueo", [
                'detalles' => [
                    ['denominacion' => 100, 'cantidad' => 4],
                    ['denominacion' => 50, 'cantidad' => 1],
                    ['denominacion' => 10, 'cantidad' => 5],
                ],
                'total_contado' => 500,
                'observaciones' => 'Arqueo de cierre',
            ]);

        $response->assertStatus(200);

        $arqueoId = $response->json('data.id');
        $this->assertDatabaseHas('arqueos_caja', [
            'id' => $arqueoId,
            'estado' => ArqueoCaja::ESTADO_REALIZADO,
            'saldo_sistema' => '500.00',
            'diferencia' => '0.00',
        ]);

        $conciliado = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/cajas/{$arqueoId}/conciliar");

        $conciliado->assertStatus(200)
            ->assertJsonPath('data.estado', 'conciliado');
    }

    public function test_conciliacion_con_diferencia_ajusta_saldo(): void
    {
        $this->caja->forceFill(['saldo_actual' => 500])->save();

        $arqueoResp = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/cajas/{$this->caja->id}/arqueo", [
                'detalles' => [
                    ['denominacion' => 100, 'cantidad' => 4],
                ],
                'total_contado' => 400,
            ]);

        $arqueoId = $arqueoResp->json('data.id');
        $this->assertSame(-100.0, (float) $arqueoResp->json('data.diferencia'));

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/cajas/{$arqueoId}/conciliar")
            ->assertStatus(200);

        $this->assertSame('400.00', $this->caja->fresh()->saldo_actual);
        $this->assertDatabaseHas('movimientos_caja', [
            'caja_id' => $this->caja->id,
            'tipo' => MovimientoCaja::EGRESO,
            'origen' => MovimientoCaja::ORIGEN_AJUSTE_ARQUEO,
            'monto' => '100.00',
        ]);
    }

    // ------------------------------------------------------------------
    // T6 — FinancialCalculatorService
    // ------------------------------------------------------------------

    public function test_financial_calculator_utiliza_costo_de_inventario(): void
    {
        $venta = $this->crearVentaCreditoCompletada(100.00);
        $this->registrarSalidaDeLote($venta, 40.00);

        $calculador = app(FinancialCalculatorService::class);

        $this->assertSame(40.0, $calculador->costoVenta($venta));
        $this->assertSame(60.0, $calculador->utilidadVenta($venta));
        $this->assertSame(60.0, $calculador->margenVenta($venta));
        $this->assertSame(0.0, $calculador->totalCobrado($venta));
        $this->assertSame(100.0, $calculador->saldoPorCobrar($venta));
    }

    public function test_venta_resource_expone_costo_y_utilidad(): void
    {
        $venta = $this->crearVentaCreditoCompletada(100.00, null, 12);
        $this->registrarSalidaDeLote($venta, 40.00);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/ventas/{$venta->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.costo_venta', 40)
            ->assertJsonPath('data.attributes.utilidad_bruta', 60)
            ->assertJsonPath('data.attributes.margen_utilidad', 60);
    }

    public function test_dashboard_finanzas_devuelve_utilidad_mensual(): void
    {
        $venta = $this->crearVentaCreditoCompletada(100.00);
        $this->registrarSalidaDeLote($venta, 40.00);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/dashboard/metrics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'finanzas' => [
                        'mensual' => [
                            'ingresos',
                            'costo_ventas',
                            'utilidad_bruta',
                            'margen_utilidad',
                            'saldo_por_cobrar',
                        ],
                    ],
                ],
            ]);

        $this->assertSame(40.0, (float) $response->json('data.finanzas.mensual.costo_ventas'));
        $this->assertSame(60.0, (float) $response->json('data.finanzas.mensual.utilidad_bruta'));
    }
}