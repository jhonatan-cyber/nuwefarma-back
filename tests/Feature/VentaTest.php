<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VentaTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    private Usuario $adminUser;

    private Sucursal $sucursal;

    private Caja $caja;

    private Cliente $cliente;

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

        $this->caja = Caja::create([
            'nombre' => 'Caja Principal',
            'numero_caja' => 1,
            'sucursal_id' => $this->sucursal->id,
            'estado' => 'abierta',
        ]);

        $this->cliente = Cliente::create([
            'ci' => '9999999',
            'nombre' => 'Cliente',
            'apellidos' => 'Test',
            'telefono' => '70000002',
            'estado' => 'activo',
        ]);

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

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $this->token = $loginResponse['data']['token'];
    }

    public function test_puede_listar_ventas(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson('/api/v1/ventas');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'type',
                            'attributes' => [
                                'numero_venta',
                                'total',
                                'metodo_pago',
                                'estado',
                            ],
                        ],
                    ],
                    'meta',
                    'links',
                ],
            ]);
    }

    public function test_puede_crear_venta(): void
    {
        $ventaData = [
            'numero_venta' => 'VNT-000001',
            'cliente_id' => $this->cliente->id,
            'sucursal_id' => $this->sucursal->id,
            'caja_id' => $this->caja->id,
            'usuario_id' => $this->adminUser->id,
            'metodo_pago' => 'efectivo',
            'tipo_pago' => 'contado',
            'subtotal' => 20.00,
            'impuesto' => 0,
            'total' => 20.00,
            'estado' => 'pendiente',
            'productos' => [
                [
                    'producto_id' => $this->producto->id,
                    'cantidad' => 2,
                    'precio_unitario' => 10.00,
                    'descuento' => 0,
                ],
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/v1/ventas', $ventaData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'type',
                    'attributes' => [
                        'numero_venta',
                        'total',
                        'metodo_pago',
                        'estado',
                    ],
                    'relationships',
                    'links',
                ],
            ]);

        $this->assertDatabaseHas('venta_productos', [
            'venta_id' => $response['data']['id'],
            'producto_id' => $this->producto->id,
            'cantidad' => 2,
        ]);
    }

    public function test_no_puede_crear_venta_sin_productos(): void
    {
        $ventaData = [
            'cliente_id' => $this->cliente->id,
            'sucursal_id' => $this->sucursal->id,
            'caja_id' => $this->caja->id,
            'metodo_pago' => 'efectivo',
            'productos' => [],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/v1/ventas', $ventaData);

        $response->assertStatus(422);
    }

    public function test_puede_completar_venta(): void
    {
        $this->producto->update(['stock_actual' => 10]);
        $this->caja->forceFill(['saldo_actual' => 100])->save();

        $venta = \App\Models\Venta::create([
            'numero_venta' => 'VNT-000001',
            'subtotal' => 20.00,
            'descuento' => 0,
            'impuestos' => 0,
            'total' => 20.00,
            'pagado' => 0,
            'saldo_pendiente' => 20.00,
            'tipo_pago' => 'contado',
            'metodo_pago' => 'efectivo',
            'estado' => 'pendiente',
            'cliente_id' => $this->cliente->id,
            'usuario_id' => $this->adminUser->id,
            'sucursal_id' => $this->sucursal->id,
            'caja_id' => $this->caja->id,
        ]);

        \App\Models\VentaProducto::create([
            'venta_id' => $venta->id,
            'producto_id' => $this->producto->id,
            'cantidad' => 2,
            'precio_unitario' => 10,
            'descuento' => 0,
            'subtotal' => 20,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->patchJson("/api/v1/ventas/{$venta->id}/completar");

        // Debug: Ver qué respuesta estamos recibiendo
        if ($response->status() !== 200) {
            dump($response->json());
        }

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.estado', 'completada');

        $this->assertSame(8, $this->producto->fresh()->stock_actual);
        $this->assertSame('120.00', $this->caja->fresh()->saldo_actual);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->patchJson("/api/v1/ventas/{$venta->id}/completar")
            ->assertStatus(409);

        $this->assertSame(8, $this->producto->fresh()->stock_actual);
        $this->assertSame('120.00', $this->caja->fresh()->saldo_actual);
    }

    public function test_puede_cancelar_venta(): void
    {
        $venta = \App\Models\Venta::create([
            'numero_venta' => 'VNT-000002',
            'subtotal' => 20.00,
            'descuento' => 0,
            'impuestos' => 0,
            'total' => 20.00,
            'pagado' => 0,
            'saldo_pendiente' => 20.00,
            'tipo_pago' => 'contado',
            'metodo_pago' => 'efectivo',
            'estado' => 'pendiente',
            'cliente_id' => $this->cliente->id,
            'usuario_id' => $this->adminUser->id,
            'sucursal_id' => $this->sucursal->id,
            'caja_id' => $this->caja->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->patchJson("/api/v1/ventas/{$venta->id}/cancelar", [
            'motivo' => 'Cancelación de prueba',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.estado', 'cancelada');
    }

    public function test_usuario_no_autenticado_no_puede_acceder(): void
    {
        $response = $this->getJson('/api/v1/ventas');

        $response->assertStatus(401);
    }

    public function test_puede_cancelar_venta_pendiente_sin_motivo(): void
    {
        $this->producto->update(['stock_actual' => 10]);

        $venta = \App\Models\Venta::create([
            'numero_venta' => 'VNT-000003',
            'subtotal' => 20.00,
            'descuento' => 0,
            'impuestos' => 0,
            'total' => 20.00,
            'pagado' => 0,
            'saldo_pendiente' => 20.00,
            'tipo_pago' => 'contado',
            'metodo_pago' => 'efectivo',
            'estado' => 'pendiente',
            'cliente_id' => $this->cliente->id,
            'usuario_id' => $this->adminUser->id,
            'sucursal_id' => $this->sucursal->id,
            'caja_id' => $this->caja->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->patchJson("/api/v1/ventas/{$venta->id}/cancelar");

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.estado', 'cancelada');

        $this->assertSame(10, $this->producto->fresh()->stock_actual);
    }

    public function test_cancelar_venta_completada_revierte_inventario_y_caja(): void
    {
        $this->producto->update(['stock_actual' => 10]);
        $this->caja->forceFill(['saldo_actual' => 100])->save();

        $venta = \App\Models\Venta::create([
            'numero_venta' => 'VNT-000004',
            'subtotal' => 20.00,
            'descuento' => 0,
            'impuestos' => 0,
            'total' => 20.00,
            'pagado' => 20.00,
            'saldo_pendiente' => 0,
            'tipo_pago' => 'contado',
            'metodo_pago' => 'efectivo',
            'estado' => 'pendiente',
            'cliente_id' => $this->cliente->id,
            'usuario_id' => $this->adminUser->id,
            'sucursal_id' => $this->sucursal->id,
            'caja_id' => $this->caja->id,
        ]);

        \App\Models\VentaProducto::create([
            'venta_id' => $venta->id,
            'producto_id' => $this->producto->id,
            'cantidad' => 2,
            'precio_unitario' => 10,
            'descuento_unitario' => 0,
            'subtotal' => 20,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$this->token])
            ->patchJson("/api/v1/ventas/{$venta->id}/completar")
            ->assertStatus(200);

        $this->assertSame(8, $this->producto->fresh()->stock_actual);
        $this->assertSame('120.00', $this->caja->fresh()->saldo_actual);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$this->token])
            ->patchJson("/api/v1/ventas/{$venta->id}/cancelar");

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.estado', 'cancelada');

        $this->assertSame(10, $this->producto->fresh()->stock_actual);
        $this->assertSame('100.00', $this->caja->fresh()->saldo_actual);
    }

    public function test_puede_devolver_venta_completada(): void
    {
        $this->producto->update(['stock_actual' => 10]);
        $this->caja->forceFill(['saldo_actual' => 100])->save();

        $venta = \App\Models\Venta::create([
            'numero_venta' => 'VNT-000005',
            'subtotal' => 20.00,
            'descuento' => 0,
            'impuestos' => 0,
            'total' => 20.00,
            'pagado' => 20.00,
            'saldo_pendiente' => 0,
            'tipo_pago' => 'contado',
            'metodo_pago' => 'efectivo',
            'estado' => 'pendiente',
            'cliente_id' => $this->cliente->id,
            'usuario_id' => $this->adminUser->id,
            'sucursal_id' => $this->sucursal->id,
            'caja_id' => $this->caja->id,
        ]);

        \App\Models\VentaProducto::create([
            'venta_id' => $venta->id,
            'producto_id' => $this->producto->id,
            'cantidad' => 2,
            'precio_unitario' => 10,
            'descuento_unitario' => 0,
            'subtotal' => 20,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$this->token])
            ->patchJson("/api/v1/ventas/{$venta->id}/completar")
            ->assertStatus(200);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$this->token])
            ->patchJson("/api/v1/ventas/{$venta->id}/devolver");

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.estado', 'devuelta');

        $this->assertSame(10, $this->producto->fresh()->stock_actual);
        $this->assertSame('100.00', $this->caja->fresh()->saldo_actual);
    }

    public function test_no_puede_devolver_venta_no_completada(): void
    {
        $venta = \App\Models\Venta::create([
            'numero_venta' => 'VNT-000006',
            'subtotal' => 20.00,
            'descuento' => 0,
            'impuestos' => 0,
            'total' => 20.00,
            'pagado' => 0,
            'saldo_pendiente' => 20.00,
            'tipo_pago' => 'contado',
            'metodo_pago' => 'efectivo',
            'estado' => 'pendiente',
            'cliente_id' => $this->cliente->id,
            'usuario_id' => $this->adminUser->id,
            'sucursal_id' => $this->sucursal->id,
            'caja_id' => $this->caja->id,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$this->token])
            ->patchJson("/api/v1/ventas/{$venta->id}/devolver")
            ->assertStatus(409);
    }

    private function crearVentaCreditoCompletada(float $total, int $sufijo = 0): \App\Models\Venta
    {
        $venta = \App\Models\Venta::create([
            'numero_venta' => 'VNT-CREDITO-'.(1000 + $sufijo),
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
        ]);

        \App\Models\VentaProducto::create([
            'venta_id' => $venta->id,
            'producto_id' => $this->producto->id,
            'cantidad' => 2,
            'precio_unitario' => 10,
            'descuento_unitario' => 0,
            'subtotal' => 20,
        ]);

        return $venta;
    }

    public function test_puede_abonar_venta_a_credito(): void
    {
        $this->caja->forceFill(['saldo_actual' => 100])->save();

        $venta = $this->crearVentaCreditoCompletada(100.00);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$this->token])
            ->patchJson("/api/v1/ventas/{$venta->id}/abonar", ['monto' => 90.00]);

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.pagado', '90.00')
            ->assertJsonPath('data.attributes.saldo_pendiente', '10.00');

        $this->assertSame('90.00', $venta->fresh()->pagado);
        $this->assertSame('10.00', $venta->fresh()->saldo_pendiente);
        $this->assertSame('190.00', $this->caja->fresh()->saldo_actual);

        $this->assertDatabaseHas('activity_logs', [
            'accion' => 'abonar_venta',
            'registro_id' => $venta->id,
        ]);
    }

    public function test_puede_saldar_venta_con_abono_total(): void
    {
        $this->caja->forceFill(['saldo_actual' => 100])->save();

        $venta = $this->crearVentaCreditoCompletada(100.00);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$this->token])
            ->patchJson("/api/v1/ventas/{$venta->id}/abonar", ['monto' => 100.00]);

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.pagado', '100.00')
            ->assertJsonPath('data.attributes.saldo_pendiente', '0.00');

        $this->assertSame('200.00', $this->caja->fresh()->saldo_actual);
    }

    public function test_no_puede_abonar_mas_que_el_saldo_pendiente(): void
    {
        $this->caja->forceFill(['saldo_actual' => 100])->save();

        $venta = $this->crearVentaCreditoCompletada(100.00);

        $this->withHeaders(['Authorization' => 'Bearer '.$this->token])
            ->patchJson("/api/v1/ventas/{$venta->id}/abonar", ['monto' => 101.00])
            ->assertStatus(409);

        $this->assertSame('0.00', $venta->fresh()->pagado);
        $this->assertSame('100.00', $venta->fresh()->saldo_pendiente);
        $this->assertSame('100.00', $this->caja->fresh()->saldo_actual);
    }

    public function test_no_puede_abonar_venta_no_completada(): void
    {
        $venta = \App\Models\Venta::create([
            'numero_venta' => 'VNT-CREDITO-PENDIENTE',
            'subtotal' => 100.00,
            'descuento' => 0,
            'impuestos' => 0,
            'total' => 100.00,
            'pagado' => 0,
            'saldo_pendiente' => 100.00,
            'tipo_pago' => 'credito',
            'metodo_pago' => 'efectivo',
            'estado' => 'pendiente',
            'cliente_id' => $this->cliente->id,
            'usuario_id' => $this->adminUser->id,
            'sucursal_id' => $this->sucursal->id,
            'caja_id' => $this->caja->id,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$this->token])
            ->patchJson("/api/v1/ventas/{$venta->id}/abonar", ['monto' => 50.00])
            ->assertStatus(409);
    }

    public function test_no_puede_abonar_venta_saldada(): void
    {
        $venta = $this->crearVentaCreditoCompletada(100.00);

        $this->withHeaders(['Authorization' => 'Bearer '.$this->token])
            ->patchJson("/api/v1/ventas/{$venta->id}/abonar", ['monto' => 100.00]);

        $this->withHeaders(['Authorization' => 'Bearer '.$this->token])
            ->patchJson("/api/v1/ventas/{$venta->id}/abonar", ['monto' => 1.00])
            ->assertStatus(409);
    }

    public function test_cancelar_venta_con_abonos_revierte_caja_y_inventario(): void
    {
        $this->producto->update(['stock_actual' => 10]);
        $this->caja->forceFill(['saldo_actual' => 100])->save();

        $venta = $this->crearVentaCreditoCompletada(100.00);

        $this->producto->update(['stock_actual' => 8]);

        $this->withHeaders(['Authorization' => 'Bearer '.$this->token])
            ->patchJson("/api/v1/ventas/{$venta->id}/abonar", ['monto' => 40.00])
            ->assertStatus(200);

        $this->assertSame('140.00', $this->caja->fresh()->saldo_actual);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$this->token])
            ->patchJson("/api/v1/ventas/{$venta->id}/cancelar");

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.estado', 'cancelada');

        $this->assertSame(10, $this->producto->fresh()->stock_actual);
        $this->assertSame('100.00', $this->caja->fresh()->saldo_actual);
    }
}
