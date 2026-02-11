<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Usuario;
use App\Models\Cliente;
use App\Models\Sucursal;
use App\Models\Caja;
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

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $this->token = $loginResponse['data']['token'];
    }

    public function test_puede_listar_ventas(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/ventas');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta',
                'links',
            ]);
    }

    public function test_puede_crear_venta(): void
    {
        $ventaData = [
            'cliente_id' => $this->cliente->id,
            'sucursal_id' => $this->sucursal->id,
            'caja_id' => $this->caja->id,
            'metodo_pago' => 'efectivo',
            'productos' => [
                [
                    'producto_id' => $this->producto->id,
                    'cantidad' => 2,
                    'precio_unitario' => 10.00,
                    'descuento_unitario' => 0,
                ],
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/ventas', $ventaData);

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
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/ventas', $ventaData);

        $response->assertStatus(422);
    }

    public function test_puede_completar_venta(): void
    {
        $venta = \App\Models\Venta::create([
            'numero_venta' => 'VNT-000001',
            'total' => 20.00,
            'descuento' => 0,
            'impuestos' => 0,
            'metodo_pago' => 'efectivo',
            'estado' => 'pendiente',
            'cliente_id' => $this->cliente->id,
            'usuario_id' => $this->adminUser->id,
            'sucursal_id' => $this->sucursal->id,
            'caja_id' => $this->caja->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->patchJson("/api/ventas/{$venta->id}/completar");

        // Debug: Ver qué respuesta estamos recibiendo
        if ($response->status() !== 200) {
            dd($response->json());
        }

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.estado', 'completada');
    }

    public function test_puede_cancelar_venta(): void
    {
        $venta = \App\Models\Venta::create([
            'numero_venta' => 'VNT-000002',
            'total' => 20.00,
            'descuento' => 0,
            'impuestos' => 0,
            'metodo_pago' => 'efectivo',
            'estado' => 'pendiente',
            'cliente_id' => $this->cliente->id,
            'usuario_id' => $this->adminUser->id,
            'sucursal_id' => $this->sucursal->id,
            'caja_id' => $this->caja->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->patchJson("/api/ventas/{$venta->id}/cancelar");

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.estado', 'cancelada');
    }

    public function test_usuario_no_autenticado_no_puede_acceder(): void
    {
        $response = $this->getJson('/api/ventas');

        $response->assertStatus(401);
    }
}
