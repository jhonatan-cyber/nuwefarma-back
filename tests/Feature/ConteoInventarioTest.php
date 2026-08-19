<?php

namespace Tests\Feature;

use App\Enums\CondicionVentaEnum;
use App\Models\Categoria;
use App\Models\ConteoInventario;
use App\Models\ConteoInventarioItem;
use App\Models\Lote;
use App\Models\MovimientoLote;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ConteoInventarioTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    private Usuario $usuario;

    private Sucursal $sucursal;

    private Producto $producto;

    private Lote $lote;

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
        $this->usuario->update(['sucursal_id' => $this->sucursal->id]);

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

        $this->producto = Producto::create([
            'nombre' => 'Paracetamol 500mg',
            'codigo_barras' => '3000000000001',
            'categoria_id' => $categoria->id,
            'condicion_venta' => CondicionVentaEnum::VENTA_LIBRE,
            'stock_actual' => 30,
            'stock_minimo' => 5,
            'precio_venta' => 20.00,
            'precio_compra' => 8.00,
            'impuesto' => 0,
            'estado' => 'activo',
        ]);

        $this->lote = Lote::create([
            'producto_id' => $this->producto->id,
            'numero_lote' => 'LOT-CNT-0001',
            'fecha_vencimiento' => now()->addMonths(6),
            'stock' => 10,
            'stock_comprometido' => 0,
            'stock_minimo' => 2,
            'stock_maximo' => 100,
            'precio_costo' => 8.00,
            'precio_costo_promedio' => 8.00,
            'estado' => 'disponible',
        ]);
    }

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    private function crearConteo(array $overrides = []): ConteoInventario
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/conteos-inventario', array_merge([
                'tipo' => 'fisico',
                'fecha_programada' => now()->toDateString(),
            ], $overrides));

        $response->assertStatus(201);

        return ConteoInventario::findOrFail($response->json('data.id'));
    }

    public function test_crea_conteo_con_items_de_los_lotes(): void
    {
        $conteo = $this->crearConteo();

        $this->assertSame(1, $conteo->total_items);
        $item = $conteo->items()->first();
        $this->assertSame(10, $item->stock_sistema);
        $this->assertSame(ConteoInventarioItem::ESTADO_PENDIENTE, $item->estado);
    }

    public function test_permite_filtrar_por_producto(): void
    {
        $categoria = $this->producto->categoria;
        $sinStock = Producto::create([
            'nombre' => 'Vitamina C 1g',
            'codigo_barras' => '3000000000009',
            'categoria_id' => $categoria->id,
            'condicion_venta' => CondicionVentaEnum::VENTA_LIBRE,
            'stock_actual' => 0,
            'stock_minimo' => 2,
            'precio_venta' => 15.00,
            'precio_compra' => 5.00,
            'impuesto' => 0,
            'estado' => 'activo',
        ]);

        $conteo = $this->crearConteo(['producto_ids' => [$sinStock->id]]);
        $this->assertSame(0, $conteo->total_items);

        $conteoConStock = $this->crearConteo(['producto_ids' => [$this->producto->id]]);
        $this->assertSame(1, $conteoConStock->total_items);
    }

    public function test_registra_conteo_fisico_con_diferencia(): void
    {
        $conteo = $this->crearConteo();
        $item = $conteo->items()->first();

        $response = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/conteos-inventario/{$conteo->id}/items/{$item->id}/contar", [
                'stock_fisico' => 7,
            ]);

        $response->assertStatus(200);

        $item->refresh();
        $this->assertSame(7, $item->stock_fisico);
        $this->assertSame(-3, $item->diferencia);
        $this->assertSame(ConteoInventarioItem::ESTADO_CONTADO, $item->estado);

        $conteo->refresh();
        $this->assertSame(ConteoInventario::ESTADO_EN_PROCESO, $conteo->estado);
    }

    public function test_cierra_conteo_y_aplica_ajuste_negativo(): void
    {
        $conteo = $this->crearConteo();
        $item = $conteo->items()->first();

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/conteos-inventario/{$conteo->id}/items/{$item->id}/contar", [
                'stock_fisico' => 7,
            ])
            ->assertStatus(200);

        $response = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/conteos-inventario/{$conteo->id}/cerrar");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.ajustes.0.diferencia', -3)
            ->assertJsonPath('data.ajustes.0.tipo', MovimientoLote::AJUSTE_NEGATIVO);

        $this->assertDatabaseHas('lotes', ['id' => $this->lote->id, 'stock' => 7]);

        $this->assertDatabaseHas('movimientos_lote', [
            'lote_id' => $this->lote->id,
            'tipo_movimiento' => MovimientoLote::AJUSTE_NEGATIVO,
            'cantidad' => 3,
            'documento_tipo' => 'ConteoInventario',
            'documento_id' => $conteo->id,
        ]);

        $this->assertDatabaseHas('conteos_inventario', [
            'id' => $conteo->id,
            'estado' => ConteoInventario::ESTADO_CERRADO,
        ]);
    }

    public function test_cierra_conteo_y_aplica_ajuste_positivo(): void
    {
        $conteo = $this->crearConteo();
        $item = $conteo->items()->first();

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/conteos-inventario/{$conteo->id}/items/{$item->id}/contar", [
                'stock_fisico' => 13,
            ])
            ->assertStatus(200);

        $response = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/conteos-inventario/{$conteo->id}/cerrar");

        $response->assertStatus(200)
            ->assertJsonPath('data.ajustes.0.diferencia', 3)
            ->assertJsonPath('data.ajustes.0.tipo', MovimientoLote::AJUSTE_POSITIVO);

        $this->assertDatabaseHas('lotes', ['id' => $this->lote->id, 'stock' => 13]);

        $this->assertDatabaseHas('movimientos_lote', [
            'lote_id' => $this->lote->id,
            'tipo_movimiento' => MovimientoLote::AJUSTE_POSITIVO,
            'cantidad' => 3,
        ]);
    }

    public function test_no_cierra_sin_contar_todos_los_items(): void
    {
        $conteo = $this->crearConteo();

        $response = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/conteos-inventario/{$conteo->id}/cerrar");

        $response->assertStatus(409);

        $this->assertDatabaseHas('conteos_inventario', [
            'id' => $conteo->id,
            'estado' => ConteoInventario::ESTADO_PENDIENTE,
        ]);
    }

    public function test_puede_cancelar_un_conteo_pendiente(): void
    {
        $conteo = $this->crearConteo();

        $response = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/conteos-inventario/{$conteo->id}/cancelar");

        $response->assertStatus(200);
        $this->assertDatabaseHas('conteos_inventario', [
            'id' => $conteo->id,
            'estado' => ConteoInventario::ESTADO_CANCELADO,
        ]);
    }
}