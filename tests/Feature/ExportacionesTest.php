<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Lote;
use App\Models\MovimientoLote;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExportacionesTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    private Usuario $adminUser;

    private Sucursal $sucursal;

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

        $categoria = Categoria::create([
            'nombre' => 'Medicamentos',
            'descripcion' => 'Categoría de medicamentos',
            'estado' => 'activo',
        ]);

        $producto = Producto::create([
            'nombre' => 'Paracetamol 500mg',
            'descripcion' => 'Analgésico',
            'codigo_barras' => '1234567890123',
            'precio' => 10.00,
            'stock' => 100,
            'stock_actual' => 100,
            'stock_minimo' => 10,
            'categoria_id' => $categoria->id,
            'estado' => 'activo',
        ]);

        $this->lote = Lote::create([
            'producto_id' => $producto->id,
            'numero_lote' => 'LOT-EXP',
            'fecha_vencimiento' => now()->addMonths(6),
            'stock' => 10,
            'stock_comprometido' => 0,
            'stock_minimo' => 2,
            'precio_costo' => 5.00,
            'precio_costo_promedio' => 5.00,
            'estado' => 'disponible',
        ]);

        MovimientoLote::create([
            'lote_id' => $this->lote->id,
            'tipo_movimiento' => MovimientoLote::ENTRADA_COMPRA,
            'cantidad' => 10,
            'stock_anterior' => 0,
            'stock_nuevo' => 10,
            'documento_tipo' => 'Compra',
            'usuario_id' => $this->adminUser->id,
            'sucursal_id' => $this->sucursal->id,
            'costo_unitario' => 5.00,
            'costo_total' => 50.00,
            'observaciones' => 'Ingreso inicial',
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

    public function test_puede_exportar_kardex_a_csv(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->get('/api/v1/kardex/exportar/csv?lote_id='.$this->lote->id);

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=utf-8');

        $contenido = $response->streamedContent();

        $this->assertStringContainsString('Tipo Movimiento', $contenido);
        $this->assertStringContainsString('ENTRADA_COMPRA', $contenido);
        $this->assertStringContainsString('LOT-EXP', $contenido);
    }

    public function test_puede_exportar_reporte_de_movimientos_a_csv(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->get('/api/v1/kardex/exportar/movimientos/csv');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=utf-8');

        $contenido = $response->streamedContent();

        $this->assertStringContainsString('Fecha,Tipo,Producto', $contenido);
        $this->assertStringContainsString('ENTRADA_COMPRA', $contenido);
    }

    public function test_exportar_movimientos_a_csv_filtra_por_tipo(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->get('/api/v1/kardex/exportar/movimientos/csv?tipo_movimiento='.MovimientoLote::SALIDA_VENTA);

        $response->assertStatus(200);

        $contenido = $response->streamedContent();

        $this->assertStringNotContainsString('ENTRADA_COMPRA', $contenido);
    }
}