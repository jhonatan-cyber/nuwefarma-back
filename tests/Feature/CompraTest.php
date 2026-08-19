<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Lote;
use App\Models\MovimientoLote;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CompraTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    private Usuario $adminUser;

    private Sucursal $sucursal;

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

        $this->proveedor = Proveedor::create([
            'nombre' => 'Proveedor Test',
            'nit' => '123456789',
            'telefono' => '70000002',
            'email' => 'proveedor@test.com',
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

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    private function payloadCompra(array $overrides = []): array
    {
        return array_merge([
            'proveedor_id' => $this->proveedor->id,
            'usuario_id' => $this->adminUser->id,
            'sucursal_id' => $this->sucursal->id,
            'metodo_pago' => 'efectivo',
            'descuento' => 5,
            'impuestos' => 2,
            'notas' => 'Compra de prueba',
            'productos' => [
                [
                    'producto_id' => $this->producto->id,
                    'numero_lote' => 'LOTE-ABC',
                    'cantidad' => 10,
                    'precio_unitario' => 5.00,
                    'descuento_unitario' => 0.5,
                    'fecha_vencimiento' => '2027-12-31',
                ],
            ],
        ], $overrides);
    }

    private function crearCompra(array $overrides = []): string
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/compras', $this->payloadCompra($overrides));

        $response->assertStatus(201);

        return $response['data']['id'];
    }

    public function test_usuario_no_autenticado_no_puede_acceder(): void
    {
        $this->getJson('/api/v1/compras')->assertStatus(401);
    }

    public function test_puede_listar_compras(): void
    {
        $response = $this->withHeaders($this->authHeaders())->getJson('/api/v1/compras');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'numero_compra',
                            'estado',
                            'total',
                        ],
                    ],
                    'meta',
                    'links',
                ],
            ]);
    }

    public function test_puede_crear_compra(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/compras', $this->payloadCompra());

        $response->assertStatus(201)
            ->assertJsonPath('data.estado', 'pendiente')
            ->assertJsonPath('data.subtotal', '45.00')
            ->assertJsonPath('data.descuento', '5.00')
            ->assertJsonPath('data.impuestos', '2.00')
            ->assertJsonPath('data.total', '42.00');

        $this->assertDatabaseHas('compra_productos', [
            'producto_id' => $this->producto->id,
            'cantidad' => 10,
            'cantidad_recibida' => 0,
            'numero_lote' => 'LOTE-ABC',
            'descuento_unitario' => '0.50',
        ]);
    }

    public function test_no_puede_crear_compra_sin_productos(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/compras', [
                'proveedor_id' => $this->proveedor->id,
                'productos' => [],
            ]);

        $response->assertStatus(422);
    }

    public function test_puede_recibir_compra_generando_lotes_y_kardex(): void
    {
        $compraId = $this->crearCompra();

        $response = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/compras/{$compraId}/recibir");

        $response->assertStatus(200)
            ->assertJsonPath('data.estado', 'recibida');

        $this->assertSame(10, $this->producto->fresh()->stock_actual);

        $this->assertDatabaseHas('lotes', [
            'producto_id' => $this->producto->id,
            'numero_lote' => 'LOTE-ABC',
            'compra_id' => $compraId,
            'stock' => 10,
        ]);

        $lote = Lote::where('producto_id', $this->producto->id)->first();

        $this->assertNotNull($lote);
        $this->assertDatabaseHas('movimientos_lote', [
            'lote_id' => $lote->id,
            'tipo_movimiento' => MovimientoLote::ENTRADA_COMPRA,
            'cantidad' => 10,
            'stock_anterior' => 0,
            'stock_nuevo' => 10,
        ]);
    }

    public function test_puede_recibir_compra_parcialmente(): void
    {
        $compraId = $this->crearCompra();

        $compra = \App\Models\Compra::find($compraId);
        $lineaId = $compra->productos()->first()->id;

        $response = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/compras/{$compraId}/recibir", [
                'items' => [
                    ['compra_producto_id' => $lineaId, 'cantidad_recibida' => 4],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.estado', 'pendiente');

        $this->assertSame(4, $this->producto->fresh()->stock_actual);
        $this->assertSame(4, Lote::where('producto_id', $this->producto->id)->first()->fresh()->stock);

        // Recibir el resto
        $response = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/compras/{$compraId}/recibir");

        $response->assertStatus(200)
            ->assertJsonPath('data.estado', 'recibida');

        $this->assertSame(10, $this->producto->fresh()->stock_actual);
        $this->assertSame(10, Lote::where('producto_id', $this->producto->id)->first()->fresh()->stock);
    }

    public function test_puede_cancelar_compra_pendiente_sin_tocar_inventario(): void
    {
        $compraId = $this->crearCompra();

        $response = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/compras/{$compraId}/cancelar", [
                'motivo' => 'Error en la factura',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.estado', 'cancelada');

        $this->assertSame(0, $this->producto->fresh()->stock_actual);
        $this->assertDatabaseMissing('lotes', ['producto_id' => $this->producto->id]);
    }

    public function test_puede_cancelar_compra_recibida_revirtiendo_inventario(): void
    {
        $compraId = $this->crearCompra();

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/compras/{$compraId}/recibir")
            ->assertStatus(200);

        $lote = Lote::where('producto_id', $this->producto->id)->first();

        $response = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/compras/{$compraId}/cancelar");

        $response->assertStatus(200)
            ->assertJsonPath('data.estado', 'cancelada');

        $this->assertSame(0, $this->producto->fresh()->stock_actual);
        $this->assertSame(0, $lote->fresh()->stock);

        $this->assertDatabaseHas('movimientos_lote', [
            'lote_id' => $lote->id,
            'tipo_movimiento' => MovimientoLote::SALIDA_DEVOLUCION_PROV,
            'cantidad' => 10,
            'stock_anterior' => 10,
            'stock_nuevo' => 0,
        ]);
    }

    public function test_puede_devolver_compra_recibida(): void
    {
        $compraId = $this->crearCompra();

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/compras/{$compraId}/recibir")
            ->assertStatus(200);

        $lote = Lote::where('producto_id', $this->producto->id)->first();

        $response = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/compras/{$compraId}/devolver");

        $response->assertStatus(200)
            ->assertJsonPath('data.estado', 'devuelta');

        $this->assertSame(0, $this->producto->fresh()->stock_actual);
        $this->assertSame(0, $lote->fresh()->stock);
    }

    public function test_no_puede_devolver_compra_no_recibida(): void
    {
        $compraId = $this->crearCompra();

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/compras/{$compraId}/devolver")
            ->assertStatus(409);
    }

    public function test_no_puede_recibir_compra_cancelada(): void
    {
        $compraId = $this->crearCompra();

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/compras/{$compraId}/cancelar")
            ->assertStatus(200);

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/compras/{$compraId}/recibir")
            ->assertStatus(409);
    }

    public function test_no_puede_recibir_dos_veces_la_misma_compra(): void
    {
        $compraId = $this->crearCompra();

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/compras/{$compraId}/recibir")
            ->assertStatus(200);

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/compras/{$compraId}/recibir")
            ->assertStatus(409);

        $this->assertSame(10, $this->producto->fresh()->stock_actual);
    }

    public function test_puede_eliminar_compra_pendiente(): void
    {
        $compraId = $this->crearCompra();

        $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/compras/{$compraId}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('compras', ['id' => $compraId]);
    }
}