<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Lote;
use App\Models\Notificacion;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NotificacionTest extends TestCase
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
            'stock_actual' => 100,
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

    public function test_puede_generar_alertas_y_contar_pendientes(): void
    {
        Lote::create([
            'producto_id' => $this->producto->id,
            'numero_lote' => 'LOT-BAJO',
            'fecha_vencimiento' => now()->addMonths(6),
            'stock' => 2,
            'stock_comprometido' => 0,
            'stock_minimo' => 5,
            'precio_costo' => 5.00,
            'precio_costo_promedio' => 5.00,
            'estado' => 'parcial',
        ]);

        Lote::create([
            'producto_id' => $this->producto->id,
            'numero_lote' => 'LOT-CERCA',
            'fecha_vencimiento' => now()->addDays(10),
            'stock' => 20,
            'stock_comprometido' => 0,
            'stock_minimo' => 2,
            'precio_costo' => 5.00,
            'precio_costo_promedio' => 5.00,
            'estado' => 'disponible',
        ]);

        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/notificaciones/generar-alertas')
            ->assertStatus(200)
            ->assertJsonPath('data.stock_bajo', 1)
            ->assertJsonPath('data.proximo_vencer', 1)
            ->assertJsonPath('data.vencidos', 0);

        $this->assertDatabaseCount('notificaciones', 2);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/notificaciones/count')
            ->assertStatus(200)
            ->assertJsonPath('count', 2);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/notificaciones/pendientes')
            ->assertStatus(200)
            ->assertJsonPath('count', 2);
    }

    public function test_generar_alertas_es_idempotente(): void
    {
        Lote::create([
            'producto_id' => $this->producto->id,
            'numero_lote' => 'LOT-BAJO',
            'fecha_vencimiento' => now()->addMonths(6),
            'stock' => 2,
            'stock_comprometido' => 0,
            'stock_minimo' => 5,
            'precio_costo' => 5.00,
            'precio_costo_promedio' => 5.00,
            'estado' => 'parcial',
        ]);

        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/notificaciones/generar-alertas')
            ->assertStatus(200);

        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/notificaciones/generar-alertas')
            ->assertStatus(200);

        $this->assertDatabaseCount('notificaciones', 1);
    }

    public function test_puede_marcar_notificacion_como_leida(): void
    {
        $notificacion = Notificacion::create([
            'tipo' => Notificacion::TIPO_ALERTA_SISTEMA,
            'titulo' => 'Alerta',
            'mensaje' => 'Mensaje de prueba',
            'modulo' => 'Sistema',
            'estado' => Notificacion::ESTADO_PENDIENTE,
        ]);

        $this->withHeaders($this->authHeaders())
            ->patchJson("/api/v1/notificaciones/{$notificacion->id}/leer")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSame(Notificacion::ESTADO_LEIDO, $notificacion->fresh()->estado);
        $this->assertNotNull($notificacion->fresh()->leido_at);
    }

    public function test_puede_marcar_todas_como_leidas(): void
    {
        Notificacion::create([
            'tipo' => Notificacion::TIPO_ALERTA_SISTEMA,
            'titulo' => 'Alerta 1',
            'mensaje' => 'Mensaje 1',
            'modulo' => 'Sistema',
            'estado' => Notificacion::ESTADO_PENDIENTE,
        ]);

        Notificacion::create([
            'tipo' => Notificacion::TIPO_ALERTA_SISTEMA,
            'titulo' => 'Alerta 2',
            'mensaje' => 'Mensaje 2',
            'modulo' => 'Sistema',
            'estado' => Notificacion::ESTADO_PENDIENTE,
        ]);

        $this->withHeaders($this->authHeaders())
            ->patchJson('/api/v1/notificaciones/leer-todas')
            ->assertStatus(200);

        $this->assertSame(0, Notificacion::pendientes()->count());
    }

    public function test_puede_listar_notificaciones_filtradas_por_tipo(): void
    {
        Notificacion::create([
            'tipo' => Notificacion::TIPO_ALERTA_SISTEMA,
            'titulo' => 'Alerta',
            'mensaje' => 'Mensaje',
            'modulo' => 'Sistema',
            'estado' => Notificacion::ESTADO_PENDIENTE,
        ]);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/notificaciones?tipo='.Notificacion::TIPO_ALERTA_SISTEMA)
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/notificaciones?tipo='.Notificacion::TIPO_STOCK_BAJO)
            ->assertStatus(200)
            ->assertJsonPath('data.total', 0);
    }

    public function test_puede_eliminar_notificacion(): void
    {
        $notificacion = Notificacion::create([
            'tipo' => Notificacion::TIPO_ALERTA_SISTEMA,
            'titulo' => 'Alerta',
            'mensaje' => 'Mensaje',
            'modulo' => 'Sistema',
            'estado' => Notificacion::ESTADO_PENDIENTE,
        ]);

        $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/notificaciones/{$notificacion->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('notificaciones', ['id' => $notificacion->id]);
    }
}