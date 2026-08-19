<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CotizacionTest extends TestCase
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
            'precio_venta' => 10.00,
            'stock_actual' => 10,
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

    private function crearCotizacion(array $overrides = []): Cotizacion
    {
        $data = array_merge([
            'numero_cotizacion' => 'COT-'.uniqid(),
            'cliente' => 'Cliente Test',
            'fecha' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(5)->toDateString(),
            'subtotal' => 100.00,
            'impuesto' => 0,
            'descuento' => 0,
            'total' => 100.00,
            'estado' => 'en_espera',
        ], $overrides);

        $cotizacion = Cotizacion::create($data);

        $cotizacion->productos()->create([
            'producto_id' => $this->producto->id,
            'cantidad' => 2,
            'precio_unitario' => 10.00,
        ]);

        return $cotizacion;
    }

    public function test_cotizacion_vencida_se_expira_al_listar(): void
    {
        $this->crearCotizacion([
            'numero_cotizacion' => 'COT-VENCIDA',
            'fecha_vencimiento' => now()->subDays(1)->toDateString(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson('/api/v1/cotizaciones');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.numero_cotizacion', 'COT-VENCIDA')
            ->assertJsonPath('data.0.estado', 'expirada');
    }

    public function test_cotizacion_vencida_no_expira_cotizaciones_validas(): void
    {
        $this->crearCotizacion([
            'numero_cotizacion' => 'COT-VENCIDA',
            'fecha_vencimiento' => now()->subDays(1)->toDateString(),
        ]);
        $this->crearCotizacion([
            'numero_cotizacion' => 'COT-VALIDA',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson('/api/v1/cotizaciones');

        $response->assertStatus(200);

        $vencidas = collect($response['data'])->firstWhere('numero_cotizacion', 'COT-VENCIDA');
        $validas = collect($response['data'])->firstWhere('numero_cotizacion', 'COT-VALIDA');

        $this->assertSame('expirada', $vencidas['estado']);
        $this->assertSame('en_espera', $validas['estado']);
    }

    public function test_puede_convertir_cotizacion_en_venta(): void
    {
        $this->caja->forceFill(['saldo_actual' => 100])->save();

        $cotizacion = $this->crearCotizacion(['numero_cotizacion' => 'COT-CONVERTIR']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson("/api/v1/cotizaciones/{$cotizacion->id}/convertir", [
            'caja_id' => $this->caja->id,
            'usuario_id' => $this->adminUser->id,
            'sucursal_id' => $this->sucursal->id,
            'tipo_pago' => 'contado',
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.attributes.estado', 'completada');

        $ventaId = $response['data']['id'];

        $this->assertDatabaseHas('ventas', [
            'id' => $ventaId,
            'cliente_id' => $this->cliente->id,
            'estado' => 'completada',
        ]);

        $this->assertSame(8, $this->producto->fresh()->stock_actual);
        $this->assertSame('200.00', $this->caja->fresh()->saldo_actual);

        $this->assertSame('aceptada', $cotizacion->fresh()->estado);
    }

    public function test_no_puede_convertir_cotizacion_expirada(): void
    {
        $cotizacion = $this->crearCotizacion([
            'numero_cotizacion' => 'COT-EXPIRADA',
            'fecha_vencimiento' => now()->subDays(1)->toDateString(),
        ]);
        $cotizacion->update(['estado' => 'expirada']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson("/api/v1/cotizaciones/{$cotizacion->id}/convertir", [
            'caja_id' => $this->caja->id,
            'tipo_pago' => 'contado',
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertStatus(409);
    }

    public function test_no_puede_convertir_sin_caja(): void
    {
        $cotizacion = $this->crearCotizacion(['numero_cotizacion' => 'COT-NO-CAJA']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson("/api/v1/cotizaciones/{$cotizacion->id}/convertir", [
            'tipo_pago' => 'contado',
            'metodo_pago' => 'efectivo',
        ]);

        $response->assertStatus(422);
    }
}