<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductoTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear rol y usuario
        $rol = Rol::create([
            'nombre' => 'Administrador',
            'descripcion' => 'Acceso completo',
            'permiso_id' => [],
            'estado' => 'activo',
        ]);

        $usuario = Usuario::create([
            'nombre' => 'Test',
            'apellidos' => 'User',
            'ci' => '12345678',
            'password' => Hash::make('12345678'),
            'telefono' => '70000000',
            'email' => 'test@example.com',
            'rol_id' => $rol->id,
            'foto' => 'default.jpg',
            'estado' => 'activo',
        ]);

        // Login y obtener token
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => '12345678',
        ]);

        $this->token = $loginResponse['data']['token'];
    }

    public function test_relacion_producto_categoria(): void
    {
        $categoria = Categoria::create([
            'nombre' => 'Antibióticos',
            'estado' => 'activo',
        ]);

        $producto = Producto::create([
            'nombre' => 'Amoxicilina',
            'categoria_id' => $categoria->id,
            'precio_compra' => 50.00,
            'precio_venta' => 100.00,
            'stock_actual' => 10,
            'stock_minimo' => 5,
            'estado' => 'activo',
        ]);

        $this->assertEquals($categoria->id, $producto->categoria->id);
        $this->assertCount(1, $categoria->productos);
        $this->assertEquals($producto->id, $categoria->productos->first()->id);
    }

    public function test_scope_productos_activos(): void
    {
        $categoria = Categoria::create(['nombre' => 'Test', 'estado' => 'activo']);

        Producto::create([
            'nombre' => 'Activo',
            'categoria_id' => $categoria->id,
            'precio_compra' => 10,
            'precio_venta' => 20,
            'stock_actual' => 5,
            'stock_minimo' => 1,
            'estado' => 'activo',
        ]);

        Producto::create([
            'nombre' => 'Inactivo',
            'categoria_id' => $categoria->id,
            'precio_compra' => 10,
            'precio_venta' => 20,
            'stock_actual' => 5,
            'stock_minimo' => 1,
            'estado' => 'inactivo',
        ]);

        $activos = Producto::activos()->get();
        $this->assertCount(1, $activos);
        $this->assertEquals('Activo', $activos->first()->nombre);
    }

    public function test_scope_stock_bajo(): void
    {
        $categoria = Categoria::create(['nombre' => 'Test', 'estado' => 'activo']);

        Producto::create([
            'nombre' => 'Stock OK',
            'categoria_id' => $categoria->id,
            'precio_compra' => 10,
            'precio_venta' => 20,
            'stock_actual' => 10,
            'stock_minimo' => 5,
            'estado' => 'activo',
        ]);

        Producto::create([
            'nombre' => 'Stock Bajo',
            'categoria_id' => $categoria->id,
            'precio_compra' => 10,
            'precio_venta' => 20,
            'stock_actual' => 3,
            'stock_minimo' => 5,
            'estado' => 'activo',
        ]);

        $bajo = Producto::query()->bajoStock()->get();
        $this->assertCount(1, $bajo);
        $this->assertEquals('Stock Bajo', $bajo->first()->nombre);
    }

    public function test_scope_proximos_a_vencer(): void
    {
        $categoria = Categoria::create(['nombre' => 'Test', 'estado' => 'activo']);

        Producto::create([
            'nombre' => 'Vence Pronto',
            'categoria_id' => $categoria->id,
            'precio_compra' => 10,
            'precio_venta' => 20,
            'stock_actual' => 5,
            'stock_minimo' => 1,
            'fecha_vencimiento' => now()->addDays(30),
            'estado' => 'activo',
        ]);

        Producto::create([
            'nombre' => 'Vence Tarde',
            'categoria_id' => $categoria->id,
            'precio_compra' => 10,
            'precio_venta' => 20,
            'stock_actual' => 5,
            'stock_minimo' => 1,
            'fecha_vencimiento' => now()->addDays(90),
            'estado' => 'activo',
        ]);

        // Con 60 días de alerta, solo vence pronto debería incluirse
        $proximos = Producto::proximosAVencer(60)->get();
        $this->assertCount(1, $proximos);
        $this->assertEquals('Vence Pronto', $proximos->first()->nombre);

        // Con 100 días, ambos deberían incluirse
        $proximos = Producto::proximosAVencer(100)->get();
        $this->assertCount(2, $proximos);
    }

    public function test_scope_categorias_activas(): void
    {
        Categoria::create(['nombre' => 'Activa 1', 'estado' => 'activo']);
        Categoria::create(['nombre' => 'Activa 2', 'estado' => 'activo']);
        Categoria::create(['nombre' => 'Inactiva', 'estado' => 'inactivo']);

        $activas = Categoria::activas()->get();
        $this->assertCount(2, $activas);

        $inactivas = Categoria::inactivas()->get();
        $this->assertCount(1, $inactivas);
    }

    public function test_endpoint_productos_bajo_stock(): void
    {
        $categoria = Categoria::create(['nombre' => 'Test', 'estado' => 'activo']);

        Producto::create([
            'nombre' => 'Stock Bajo',
            'categoria_id' => $categoria->id,
            'precio_compra' => 10,
            'precio_venta' => 20,
            'stock_actual' => 2,
            'stock_minimo' => 5,
            'estado' => 'activo',
        ]);

        Producto::create([
            'nombre' => 'Stock OK',
            'categoria_id' => $categoria->id,
            'precio_compra' => 10,
            'precio_venta' => 20,
            'stock_actual' => 10,
            'stock_minimo' => 5,
            'estado' => 'activo',
        ]);

        // Probar el endpoint específico
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/productos/bajo-stock');

        // Debug: ver qué está recibiendo
        if ($response->status() !== 200) {
            dump($response->json());
        }

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonFragment(['nombre' => 'Stock Bajo']);
    }

    public function test_endpoint_productos_proximo_vencer(): void
    {
        $categoria = Categoria::create(['nombre' => 'Test', 'estado' => 'activo']);

        Producto::create([
            'nombre' => 'Vence Pronto',
            'categoria_id' => $categoria->id,
            'precio_compra' => 10,
            'precio_venta' => 20,
            'stock_actual' => 5,
            'stock_minimo' => 1,
            'fecha_vencimiento' => now()->addDays(30),
            'estado' => 'activo',
        ]);

        Producto::create([
            'nombre' => 'Vence Tarde',
            'categoria_id' => $categoria->id,
            'precio_compra' => 10,
            'precio_venta' => 20,
            'stock_actual' => 5,
            'stock_minimo' => 1,
            'fecha_vencimiento' => now()->addDays(120),
            'estado' => 'activo',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/productos/proximos-vencer');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonFragment(['nombre' => 'Vence Pronto']);
    }

    public function test_puede_crear_producto_con_proveedor_fotos_y_descripcion(): void
    {
        $categoria = Categoria::create(['nombre' => 'Test', 'estado' => 'activo']);
        $proveedor = \App\Models\Proveedor::create([
            'nombre' => 'Laboratorio Test',
            'estado' => 'activo',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/productos', [
                'nombre' => 'Amoxicilina 500mg',
                'categoria_id' => $categoria->id,
                'proveedor_id' => $proveedor->id,
                'codigo_barras' => '1234567890123',
                'laboratorio' => 'Lab Test',
                'forma_farmaceutica' => 'Cápsulas',
                'concentracion' => '500 mg',
                'presentacion' => 'Caja x 20',
                'descripcion' => 'Antibiótico de amplio espectro',
                'fotos' => ['https://ejemplo.com/foto1.jpg', 'https://ejemplo.com/foto2.jpg'],
                'precio_compra' => 5,
                'precio_venta' => 12,
                'stock_actual' => 20,
                'stock_minimo' => 5,
                'estado' => 'activo',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.attributes.nombre', 'Amoxicilina 500mg')
            ->assertJsonPath('data.attributes.fotos.0', 'https://ejemplo.com/foto1.jpg')
            ->assertJsonPath('data.attributes.descripcion', 'Antibiótico de amplio espectro')
            ->assertJsonPath('data.relationships.proveedor.data.nombre', 'Laboratorio Test');

        $this->assertDatabaseHas('productos', [
            'nombre' => 'Amoxicilina 500mg',
            'proveedor_id' => $proveedor->id,
            'laboratorio' => 'Lab Test',
        ]);
    }

    public function test_show_producto_incluye_conteos_relacionados(): void
    {
        $categoria = Categoria::create(['nombre' => 'Test', 'estado' => 'activo']);

        $producto = Producto::create([
            'nombre' => 'Ibuprofeno',
            'categoria_id' => $categoria->id,
            'precio_compra' => 8,
            'precio_venta' => 15,
            'stock_actual' => 10,
            'stock_minimo' => 2,
            'estado' => 'activo',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/productos/{$producto->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.nombre', 'Ibuprofeno')
            ->assertJsonPath('data.attributes.lotes_count', 0)
            ->assertJsonPath('data.attributes.venta_productos_count', 0)
            ->assertJsonPath('data.attributes.compra_productos_count', 0);
    }
}
