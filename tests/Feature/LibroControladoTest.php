<?php

namespace Tests\Feature;

use App\Enums\CondicionVentaEnum;
use App\Models\Categoria;
use App\Models\Lote;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LibroControladoTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $rol = Rol::create([
            'nombre' => 'Administrador',
            'descripcion' => 'Acceso completo',
            'permiso_id' => [],
            'estado' => 'activo',
        ]);

        $usuario = Usuario::create([
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
        $usuario->update(['sucursal_id' => $sucursal->id]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);
        $this->token = $login['data']['token'];
    }

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    private function completarDispensacionDeControlado(): void
    {
        $categoria = Categoria::create([
            'nombre' => 'Controlados',
            'descripcion' => 'Categoría',
            'estado' => 'activo',
        ]);

        $producto = Producto::create([
            'nombre' => 'Tramadol 50mg',
            'codigo_barras' => '4000000000001',
            'categoria_id' => $categoria->id,
            'condicion_venta' => CondicionVentaEnum::RECETA_RETENIDA,
            'stock_actual' => 50,
            'stock_minimo' => 5,
            'precio_venta' => 30.00,
            'precio_compra' => 10.00,
            'impuesto' => 0,
            'estado' => 'activo',
        ]);

        $lote = Lote::create([
            'producto_id' => $producto->id,
            'numero_lote' => 'LOT-CTRL-0001',
            'fecha_vencimiento' => now()->addMonths(6),
            'stock' => 20,
            'stock_comprometido' => 0,
            'stock_minimo' => 2,
            'stock_maximo' => 100,
            'precio_costo' => 10.00,
            'precio_costo_promedio' => 10.00,
            'estado' => 'disponible',
        ]);

        $medico = Medico::create([
            'nombres' => 'Dr. Luis',
            'apellidos' => 'García',
            'ci' => '555111',
            'registro_profesional' => 'MDE-002',
            'especialidad' => 'Anestesiología',
            'estado' => 'activo',
        ]);

        $paciente = Paciente::create([
            'ci' => '777999',
            'nombres' => 'María',
            'apellidos' => 'Flores',
            'fecha_nacimiento' => '1985-03-15',
            'sexo' => 'F',
            'estado' => 'activo',
        ]);

        $receta = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/recetas', [
                'medico_id' => $medico->id,
                'paciente_id' => $paciente->id,
                'fecha_emision' => now()->toDateString(),
                'productos' => [
                    ['producto_id' => $producto->id, 'cantidad' => 3],
                ],
            ])
            ->assertStatus(201);
        $recetaId = $receta->json('data.id');

        $recetaProductoId = \App\Models\RecetaProducto::where('receta_id', $recetaId)->first()->id;

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/recetas/{$recetaId}/dispensar", [
                'items' => [
                    ['receta_producto_id' => $recetaProductoId, 'cantidad' => 3, 'lote_id' => $lote->id],
                ],
                'autorizacion_controlado' => 'AUT-999',
            ])
            ->assertStatus(200);
    }

    public function test_puede_listar_movimientos_del_libro(): void
    {
        $this->completarDispensacionDeControlado();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/libro-controlados');

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.autorizacion', 'AUT-999')
            ->assertJsonPath('data.data.0.producto.nombre', 'Tramadol 50mg');
    }

    public function test_genera_reporte_regulatorio_por_producto(): void
    {
        $this->completarDispensacionDeControlado();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/libro-controlados/reporte');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.resumen.total_movimientos', 1)
            ->assertJsonPath('data.resumen.total_unidades', 3)
            ->assertJsonPath('data.por_producto.0.producto', 'Tramadol 50mg')
            ->assertJsonPath('data.por_producto.0.total_unidades', 3);
    }

    public function test_reporte_respeta_periodo(): void
    {
        $this->completarDispensacionDeControlado();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/libro-controlados/reporte?fecha_inicio='.now()->addMonth()->startOfMonth()->toDateString().'&fecha_fin='.now()->addMonth()->endOfMonth()->toDateString());

        $response->assertStatus(200)
            ->assertJsonPath('data.resumen.total_movimientos', 0);
    }
}