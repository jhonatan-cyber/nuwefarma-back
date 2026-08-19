<?php

namespace Tests\Feature;

use App\Enums\CondicionVentaEnum;
use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Factura;
use App\Models\Producto;
use App\Models\PuntoVenta;
use App\Models\Rol;
use App\Models\SiatCredencial;
use App\Models\SiatSesion;
use App\Models\SiatTransaccion;
use App\Models\Sucursal;
use App\Models\Usuario;
use App\Models\Venta;
use App\Models\VentaProducto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FacturaFiscalTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    private Usuario $usuario;

    private Sucursal $sucursal;

    private Caja $caja;

    private Producto $producto;

    private Cliente $cliente;

    private PuntoVenta $puntoVenta;

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

        $this->caja = Caja::create([
            'nombre' => 'Caja Principal',
            'numero_caja' => 1,
            'saldo_actual' => 100,
            'saldo_inicial' => 100,
            'estado' => 'abierta',
            'sucursal_id' => $this->sucursal->id,
            'usuario_id' => $this->usuario->id,
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);
        $this->token = $login['data']['token'];

        $this->cliente = Cliente::create([
            'ci' => '9990011',
            'nombre' => 'Juan',
            'apellidos' => 'Perez',
            'nit' => '4020401',
            'direccion' => 'Av. Central 100',
            'telefono' => '71234567',
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
            'stock_actual' => 10,
            'stock_minimo' => 5,
            'stock_maximo' => 50,
            'precio_venta' => 20.00,
            'precio_compra' => 8.00,
            'impuesto' => 0,
            'estado' => 'activo',
        ]);

        $this->puntoVenta = PuntoVenta::create([
            'sucursal_id' => $this->sucursal->id,
            'codigo_poa' => PuntoVenta::generarCodigoPoa(),
            'nombre' => 'Punto de Venta Principal',
            'tipo' => 'fisica',
            'ambiente' => 'pruebas',
            'estado' => 'activo',
        ]);
    }

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    private function crearVentaCompletada(string $numero = 'VNT-FISCAL-001'): Venta
    {
        $venta = Venta::create([
            'numero_venta' => $numero,
            'subtotal' => 40.00,
            'descuento' => 0,
            'impuestos' => 0,
            'total' => 40.00,
            'pagado' => 40.00,
            'saldo_pendiente' => 0,
            'tipo_pago' => 'contado',
            'metodo_pago' => 'efectivo',
            'estado' => 'completada',
            'cliente_id' => $this->cliente->id,
            'usuario_id' => $this->usuario->id,
            'sucursal_id' => $this->sucursal->id,
            'caja_id' => $this->caja->id,
        ]);

        VentaProducto::create([
            'venta_id' => $venta->id,
            'producto_id' => $this->producto->id,
            'cantidad' => 2,
            'precio_unitario' => 20,
            'descuento_unitario' => 0,
            'subtotal' => 40,
        ]);

        return $venta;
    }

    public function test_estado_configuracion_muestra_provider_simulado(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/siat/configuracion')
            ->assertStatus(200);

        $response->assertJsonPath('data.provider', 'simulated')
            ->assertJsonPath('data.provider_simulado', true)
            ->assertJsonPath('data.ambiente', 'pruebas')
            ->assertJsonPath('data.codigo_sistema', 'NuweFarmaPOA');
    }

    public function test_guarda_credencial_cifrada(): void
    {
        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/siat/credenciales', [
                'nombre' => 'token_pruebas',
                'valor' => 'secret-token-123',
                'ambiente' => 'pruebas',
            ])
            ->assertStatus(201);

        $credencial = SiatCredencial::where('nombre', 'token_pruebas')->first();
        $this->assertNotNull($credencial);
        $this->assertStringNotContainsString('secret-token-123', $credencial->valor_cifrado);
        $this->assertSame('secret-token-123', $credencial->valor);
    }

    public function test_crea_empresa_fiscal_y_la_actualiza(): void
    {
        $empresa = Empresa::obtenerODefault();
        $this->assertSame('NuweFarma S.R.L.', $empresa->razon_social);

        $this->withHeaders($this->authHeaders())
            ->putJson('/api/v1/empresa-fiscal', [
                'nit' => '123456789',
                'razon_social' => 'Farmacia Central S.R.L.',
                'codigo_actividad' => '477310',
                'municipio' => 'Cochabamba',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.nit', '123456789')
            ->assertJsonPath('data.razon_social', 'Farmacia Central S.R.L.')
            ->assertJsonPath('data.municipio', 'Cochabamba');

        $this->assertSame('Farmacia Central S.R.L.', Empresa::obtenerODefault()->razon_social);
    }

    public function test_solicita_cuis_y_cufd_del_punto_de_venta(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/puntos-venta/{$this->puntoVenta->id}/sesiones")
            ->assertStatus(200);

        $response->assertJsonPath('data.cuis.tipo', 'cuis')
            ->assertJsonPath('data.cufd.tipo', 'cufd');

        $this->assertDatabaseHas('siat_sesiones', ['punto_venta_id' => $this->puntoVenta->id, 'tipo' => 'cuis']);
        $this->assertDatabaseHas('siat_sesiones', ['punto_venta_id' => $this->puntoVenta->id, 'tipo' => 'cufd']);
        $this->assertDatabaseHas('siat_transacciones', ['tipo_operacion' => 'solicitar_cuis']);
        $this->assertDatabaseHas('siat_transacciones', ['tipo_operacion' => 'solicitar_cufd']);

        $this->assertNotEmpty(SiatSesion::vigente($this->puntoVenta->id, SiatSesion::TIPO_CUIS)->codigo);
    }

    public function test_emite_factura_con_detalles_y_numero_consecutivo(): void
    {
        $venta = $this->crearVentaCompletada();

        $emitida = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/facturas/emitir', [
                'venta_id' => $venta->id,
                'punto_venta_id' => $this->puntoVenta->id,
            ])
            ->assertStatus(201);

        $emitida->assertJsonPath('data.estado', 'emitida')
            ->assertJsonPath('data.tipo_emision', 'online')
            ->assertJsonPath('data.numero_factura', '00001')
            ->assertJsonPath('data.monto_total', 40)
            ->assertJsonPath('data.razon_social_cliente', 'Juan Perez');

        $cuf = $emitida->json('data.cuf');
        $this->assertNotEmpty($cuf);
        $this->assertSame(32, strlen($cuf));
        $this->assertNotEmpty($emitida->json('data.cuis'));
        $this->assertNotEmpty($emitida->json('data.cufd'));
        $this->assertNotEmpty($emitida->json('data.codigo_control'));
        $this->assertNotEmpty($emitida->json('data.qr'));

        $this->assertCount(1, $emitida->json('data.detalles'));
        $this->assertSame(2, $emitida->json('data.detalles.0.cantidad'));
        $this->assertSame(20, $emitida->json('data.detalles.0.precio_unitario'));

        $this->assertDatabaseHas('facturas', ['venta_id' => $venta->id, 'numero_factura' => '00001']);
        $this->assertDatabaseHas('factura_detalles', ['descripcion' => 'Paracetamol 500mg']);
        $this->assertDatabaseHas('siat_transacciones', ['tipo_operacion' => 'emitir']);

        // Segunda factura usa el siguiente número consecutivo.
        $venta2 = $this->crearVentaCompletada('VNT-FISCAL-002');

        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/facturas/emitir', [
                'venta_id' => $venta2->id,
                'punto_venta_id' => $this->puntoVenta->id,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.numero_factura', '00002');
    }

    public function test_no_emite_doble_factura_para_la_misma_venta(): void
    {
        $venta = $this->crearVentaCompletada();

        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/facturas/emitir', [
                'venta_id' => $venta->id,
                'punto_venta_id' => $this->puntoVenta->id,
            ])
            ->assertStatus(201);

        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/facturas/emitir', [
                'venta_id' => $venta->id,
                'punto_venta_id' => $this->puntoVenta->id,
            ])
            ->assertStatus(409);

        $this->assertSame(1, Factura::where('venta_id', $venta->id)->count());
    }

    public function test_anula_factura_emitida(): void
    {
        $venta = $this->crearVentaCompletada();

        $emitida = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/facturas/emitir', [
                'venta_id' => $venta->id,
                'punto_venta_id' => $this->puntoVenta->id,
            ])
            ->assertStatus(201);

        $facturaId = $emitida->json('data.id');

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/facturas/{$facturaId}/anular", [
                'codigo_motivo' => '1',
                'motivo_anulacion' => 'Datos del cliente incorrectos',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.estado', 'anulada')
            ->assertJsonPath('data.motivo_anulacion', 'Datos del cliente incorrectos');

        $this->assertDatabaseHas('facturas', ['id' => $facturaId, 'estado' => 'anulada']);
        $this->assertDatabaseHas('siat_transacciones', ['tipo_operacion' => 'anular', 'cuf' => $emitida->json('data.cuf')]);
    }

    public function test_emite_factura_en_contingencia(): void
    {
        $venta = $this->crearVentaCompletada();

        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/facturas/contingencia', [
                'venta_id' => $venta->id,
                'punto_venta_id' => $this->puntoVenta->id,
                'motivo' => 'Falla de comunicación con el SIN',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.tipo_emision', 'contingencia');

        $this->assertDatabaseHas('facturas', ['venta_id' => $venta->id, 'tipo_emision' => 'contingencia']);
    }

    public function test_consulta_estado_de_factura(): void
    {
        $venta = $this->crearVentaCompletada();

        $emitida = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/facturas/emitir', [
                'venta_id' => $venta->id,
                'punto_venta_id' => $this->puntoVenta->id,
            ])
            ->assertStatus(201);

        $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/facturas/{$emitida->json('data.id')}/consultar")
            ->assertStatus(200)
            ->assertJsonPath('data.estado', 'emitida')
            ->assertJsonPath('data.cuf', $emitida->json('data.cuf'));
    }

    public function test_lista_facturas_y_puntos_venta(): void
    {
        $venta = $this->crearVentaCompletada();
        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/facturas/emitir', [
                'venta_id' => $venta->id,
                'punto_venta_id' => $this->puntoVenta->id,
            ])
            ->assertStatus(201);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/facturas')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.estado', 'emitida');

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/puntos-venta')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.codigo_poa', $this->puntoVenta->codigo_poa);
    }

    public function test_representacion_incluye_qr_y_pie_seguridad(): void
    {
        $venta = $this->crearVentaCompletada();

        $emitida = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/facturas/emitir', [
                'venta_id' => $venta->id,
                'punto_venta_id' => $this->puntoVenta->id,
            ])
            ->assertStatus(201);

        $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/facturas/{$emitida->json('data.id')}/representacion")
            ->assertStatus(200)
            ->assertJsonPath('data.encabezado.razon_social', 'NuweFarma S.R.L.')
            ->assertJsonPath('data.qr', $emitida->json('data.qr'))
            ->assertJsonPath('data.leyenda', config('siat.leyenda'));
    }
}