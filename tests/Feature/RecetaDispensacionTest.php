<?php

namespace Tests\Feature;

use App\Enums\CondicionVentaEnum;
use App\Models\Categoria;
use App\Models\Lote;
use App\Models\Medico;
use App\Models\MovimientoLote;
use App\Models\Paciente;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RecetaDispensacionTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    private Usuario $adminUser;

    private Sucursal $sucursal;

    private Medico $medico;

    private Paciente $paciente;

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

        $this->medico = Medico::create([
            'nombres' => 'Dra. Ana',
            'apellidos' => 'Pérez',
            'ci' => '555555',
            'registro_profesional' => 'MDE-001',
            'especialidad' => 'Pediatría',
            'telefono' => '71111111',
            'email' => 'ana@med.com',
            'estado' => 'activo',
        ]);

        $this->paciente = Paciente::create([
            'ci' => '777777',
            'nombres' => 'Carlos',
            'apellidos' => 'Quispe',
            'fecha_nacimiento' => '2010-05-10',
            'sexo' => 'M',
            'telefono' => '72222222',
            'estado' => 'activo',
            'sucursal_id' => $this->sucursal->id,
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

    private function crearProducto(string $nombre, string $codigo, CondicionVentaEnum $condicion = CondicionVentaEnum::VENTA_LIBRE): Producto
    {
        $categoria = Categoria::first() ?? Categoria::create([
            'nombre' => 'Medicamentos',
            'descripcion' => 'Categoría',
            'estado' => 'activo',
        ]);

        return Producto::create([
            'nombre' => $nombre,
            'codigo_barras' => $codigo,
            'categoria_id' => $categoria->id,
            'condicion_venta' => $condicion,
            'principio_activo' => 'TEST',
            'stock_actual' => 100,
            'stock_minimo' => 5,
            'precio_venta' => 25.00,
            'precio_compra' => 10.00,
            'impuesto' => 0,
            'estado' => 'activo',
        ]);
    }

    private function crearLote(Producto $producto, int $stock): Lote
    {
        return Lote::create([
            'producto_id' => $producto->id,
            'numero_lote' => 'LOT-'.fake()->unique()->numerify('###'),
            'fecha_vencimiento' => now()->addMonths(6),
            'stock' => $stock,
            'stock_comprometido' => 0,
            'stock_minimo' => 2,
            'stock_maximo' => 100,
            'precio_costo' => 10.00,
            'precio_costo_promedio' => 10.00,
            'estado' => 'disponible',
        ]);
    }

    public function test_puede_crear_una_receta_con_sus_productos(): void
    {
        $producto = $this->crearProducto('Paracetamol 500mg', '1000000000001');

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/recetas', [
                'medico_id' => $this->medico->id,
                'paciente_id' => $this->paciente->id,
                'fecha_emision' => now()->toDateString(),
                'productos' => [
                    ['producto_id' => $producto->id, 'cantidad' => 10, 'posologia' => 'Cada 8 horas'],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'recetas')
            ->assertJsonPath('data.attributes.estado', 'pendiente');

        $recetaId = $response->json('data.id');
        $this->assertDatabaseHas('receta_productos', [
            'receta_id' => $recetaId,
            'producto_id' => $producto->id,
            'cantidad_prescrita' => '10.00',
            'estado' => 'pendiente',
        ]);
    }

    public function test_puede_dispensar_parcial_una_receta(): void
    {
        $producto = $this->crearProducto('Ibuprofeno 400mg', '1000000000002');
        $lote = $this->crearLote($producto, 50);

        $receta = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/recetas', [
                'medico_id' => $this->medico->id,
                'paciente_id' => $this->paciente->id,
                'fecha_emision' => now()->toDateString(),
                'productos' => [
                    ['producto_id' => $producto->id, 'cantidad' => 20],
                ],
            ]);
        $recetaId = $receta->json('data.id');
        $recetaProductoId = \App\Models\RecetaProducto::where('receta_id', $recetaId)->first()->id;

        $response = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/recetas/{$recetaId}/dispensar", [
                'items' => [
                    ['receta_producto_id' => $recetaProductoId, 'cantidad' => 5, 'lote_id' => $lote->id],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.attributes.estado', 'parcial');

        $this->assertDatabaseHas('receta_productos', [
            'id' => $recetaProductoId,
            'cantidad_dispensada' => '5.00',
            'estado' => 'parcial',
        ]);

        $this->assertDatabaseHas('lotes', ['id' => $lote->id, 'stock' => 45]);

        $this->assertDatabaseHas('movimientos_lote', [
            'lote_id' => $lote->id,
            'tipo_movimiento' => MovimientoLote::SALIDA_DISPENSACION,
            'cantidad' => 5,
            'documento_id' => $recetaId,
            'documento_tipo' => 'Receta',
        ]);
    }

    public function test_completa_la_receta_al_dispensar_todo(): void
    {
        $producto = $this->crearProducto('Amoxicilina 500mg', '1000000000003');
        $lote = $this->crearLote($producto, 30);

        $receta = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/recetas', [
                'medico_id' => $this->medico->id,
                'paciente_id' => $this->paciente->id,
                'fecha_emision' => now()->toDateString(),
                'productos' => [
                    ['producto_id' => $producto->id, 'cantidad' => 10],
                ],
            ]);
        $recetaId = $receta->json('data.id');
        $recetaProductoId = \App\Models\RecetaProducto::where('receta_id', $recetaId)->first()->id;

        $response = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/recetas/{$recetaId}/dispensar", [
                'items' => [
                    ['receta_producto_id' => $recetaProductoId, 'cantidad' => 10, 'lote_id' => $lote->id],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.estado', 'atendida');

        $this->assertDatabaseHas('receta_productos', [
            'id' => $recetaProductoId,
            'estado' => 'dispensado',
        ]);
    }

    public function test_no_permite_dispensar_mas_de_lo_prescrito(): void
    {
        $producto = $this->crearProducto('Loratadina 10mg', '1000000000004');
        $lote = $this->crearLote($producto, 50);

        $receta = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/recetas', [
                'medico_id' => $this->medico->id,
                'paciente_id' => $this->paciente->id,
                'fecha_emision' => now()->toDateString(),
                'productos' => [
                    ['producto_id' => $producto->id, 'cantidad' => 5],
                ],
            ]);
        $recetaId = $receta->json('data.id');
        $recetaProductoId = \App\Models\RecetaProducto::where('receta_id', $recetaId)->first()->id;

        $response = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/recetas/{$recetaId}/dispensar", [
                'items' => [
                    ['receta_producto_id' => $recetaProductoId, 'cantidad' => 999, 'lote_id' => $lote->id],
                ],
            ]);

        $response->assertStatus(409);
        $this->assertDatabaseHas('lotes', ['id' => $lote->id, 'stock' => 50]);
    }

    public function test_exige_autorizacion_para_controlados(): void
    {
        $producto = $this->crearProducto('Tramadol 50mg', '1000000000005', CondicionVentaEnum::RECETA_RETENIDA);
        $lote = $this->crearLote($producto, 20);

        $receta = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/recetas', [
                'medico_id' => $this->medico->id,
                'paciente_id' => $this->paciente->id,
                'fecha_emision' => now()->toDateString(),
                'productos' => [
                    ['producto_id' => $producto->id, 'cantidad' => 2],
                ],
            ]);
        $recetaId = $receta->json('data.id');
        $recetaProductoId = \App\Models\RecetaProducto::where('receta_id', $recetaId)->first()->id;

        $sinAutorizacion = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/recetas/{$recetaId}/dispensar", [
                'items' => [
                    ['receta_producto_id' => $recetaProductoId, 'cantidad' => 2, 'lote_id' => $lote->id],
                ],
            ]);

        $sinAutorizacion->assertStatus(409);

        $conAutorizacion = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/recetas/{$recetaId}/dispensar", [
                'items' => [
                    ['receta_producto_id' => $recetaProductoId, 'cantidad' => 2, 'lote_id' => $lote->id],
                ],
                'autorizacion_controlado' => 'AUT-001',
            ]);

        $conAutorizacion->assertStatus(200);

        $this->assertDatabaseHas('libro_controlados', [
            'producto_id' => $producto->id,
            'receta_id' => $recetaId,
            'autorizacion' => 'AUT-001',
            'cantidad' => '2.00',
        ]);
    }

    public function test_puede_anular_una_receta_pendiente(): void
    {
        $producto = $this->crearProducto('Cetirizina 10mg', '1000000000006');

        $receta = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/recetas', [
                'medico_id' => $this->medico->id,
                'paciente_id' => $this->paciente->id,
                'fecha_emision' => now()->toDateString(),
                'productos' => [
                    ['producto_id' => $producto->id, 'cantidad' => 10],
                ],
            ]);
        $recetaId = $receta->json('data.id');

        $response = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/recetas/{$recetaId}/anular", [
                'motivo' => 'Cambio de tratamiento',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.estado', 'anulada');

        $this->assertDatabaseHas('receta_productos', [
            'receta_id' => $recetaId,
            'estado' => 'anulado',
        ]);
    }

    public function test_no_dispensa_receta_vencida(): void
    {
        $producto = $this->crearProducto('Dexametasona 4mg', '1000000000007');
        $lote = $this->crearLote($producto, 20);

        $receta = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/recetas', [
                'medico_id' => $this->medico->id,
                'paciente_id' => $this->paciente->id,
                'fecha_emision' => now()->subDays(20)->toDateString(),
                'fecha_vencimiento' => now()->subDays(5)->toDateString(),
                'productos' => [
                    ['producto_id' => $producto->id, 'cantidad' => 4],
                ],
            ]);
        $recetaId = $receta->json('data.id');
        $recetaProductoId = \App\Models\RecetaProducto::where('receta_id', $recetaId)->first()->id;

        $response = $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/recetas/{$recetaId}/dispensar", [
                'items' => [
                    ['receta_producto_id' => $recetaProductoId, 'cantidad' => 4, 'lote_id' => $lote->id],
                ],
            ]);

        $response->assertStatus(409);

        $this->assertDatabaseHas('lotes', ['id' => $lote->id, 'stock' => 20]);
    }

    public function test_puede_listar_y_marcar_recetas_vencidas(): void
    {
        $producto = $this->crearProducto('Omeprazol 20mg', '1000000000008');

        $receta = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/recetas', [
                'medico_id' => $this->medico->id,
                'paciente_id' => $this->paciente->id,
                'fecha_emision' => now()->subDays(30)->toDateString(),
                'fecha_vencimiento' => now()->subDays(10)->toDateString(),
                'productos' => [
                    ['producto_id' => $producto->id, 'cantidad' => 1],
                ],
            ]);
        $recetaId = $receta->json('data.id');

        $list = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/recetas');
        $list->assertStatus(200);
        $this->assertGreaterThan(0, $list->json('data.meta.total'));

        $marcar = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/recetas/marcar-vencidas');
        $marcar->assertStatus(200);

        $this->assertDatabaseHas('recetas', [
            'id' => $recetaId,
            'estado' => 'vencida',
        ]);
    }
}