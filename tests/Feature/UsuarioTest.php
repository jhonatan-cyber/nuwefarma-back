<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Usuario;
use App\Models\Sucursal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsuarioTest extends TestCase
{
    use RefreshDatabase;

    private string $token;
    private Usuario $adminUser;
    private Rol $adminRol;
    private Rol $gerenteRol;
    private Sucursal $sucursal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRol = Rol::create([
            'nombre' => 'Administrador',
            'descripcion' => 'Acceso completo',
            'permiso_id' => [],
            'estado' => 'activo',
        ]);

        $this->gerenteRol = Rol::create([
            'nombre' => 'Gerente',
            'descripcion' => 'Acceso limitado',
            'permiso_id' => [],
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

        $this->adminUser = Usuario::create([
            'nombre' => 'Test',
            'apellidos' => 'Admin',
            'ci' => '12345678',
            'password' => Hash::make('password123'),
            'telefono' => '70000000',
            'email' => 'admin@test.com',
            'rol_id' => $this->adminRol->id,
            'foto' => 'default.jpg',
            'estado' => 'activo',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        // Debug: Ver qué respuesta estamos recibiendo
        $loginResponse->assertStatus(200);
        
        $this->token = $loginResponse['data']['token'];
    }

    public function test_puede_listar_usuarios(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/usuarios');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'type',
                            'attributes' => [
                                'nombre',
                                'apellidos',
                                'email',
                                'estado',
                            ],
                            'relationships' => [
                                'rol' => ['data' => ['nombre']],
                            ],
                        ],
                    ],
                    'meta',
                    'links',
                ],
            ]);
    }

    public function test_puede_crear_usuario(): void
    {
        $usuarioData = [
            'nombre' => 'Nuevo',
            'apellidos' => 'Usuario',
            'ci' => '99999999',
            'email' => 'nuevo@test.com',
            'password' => 'password123',
            'telefono' => '70000001',
            'rol_id' => $this->adminRol->id,
            'foto' => 'default.jpg',
            'estado' => 'activo',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/usuarios', $usuarioData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'type',
                    'attributes' => [
                        'nombre',
                        'apellidos',
                        'email',
                    ],
                    'relationships',
                    'links',
                ],
            ]);

        $this->assertDatabaseHas('usuarios', [
            'email' => 'nuevo@test.com',
            'nombre' => 'Nuevo',
        ]);
    }

    public function test_no_puede_crear_usuario_con_email_duplicado(): void
    {
        $usuarioData = [
            'nombre' => 'Nuevo',
            'apellidos' => 'Usuario',
            'ci' => '99999999',
            'email' => 'admin@test.com',
            'password' => 'password123',
            'telefono' => '70000001',
            'rol_id' => $this->adminRol->id,
            'estado' => 'activo',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/usuarios', $usuarioData);

        $response->assertStatus(422);
    }

    public function test_puede_actualizar_usuario(): void
    {
        $usuario = Usuario::create([
            'nombre' => 'Usuario',
            'apellidos' => 'Test',
            'ci' => '88888888',
            'password' => Hash::make('password123'),
            'telefono' => '70000002',
            'email' => 'usuariotest@test.com',
            'rol_id' => $this->gerenteRol->id,
            'foto' => 'default.jpg',
            'estado' => 'activo',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/usuarios/{$usuario->id}", [
            'nombre' => 'Usuario Actualizado',
            'apellidos' => 'Test Actualizado',
            'ci' => '88888888',
            'email' => 'usuariotest@test.com',
            'rol_id' => $this->gerenteRol->id,
            'telefono' => '70000003',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.nombre', 'Usuario Actualizado');

        $this->assertDatabaseHas('usuarios', [
            'id' => $usuario->id,
            'nombre' => 'Usuario Actualizado',
            'telefono' => '70000003',
        ]);
    }

    public function test_puede_cambiar_estado_de_usuario(): void
    {
        $usuario = Usuario::create([
            'nombre' => 'Usuario',
            'apellidos' => 'Test',
            'ci' => '77777777',
            'password' => Hash::make('password123'),
            'telefono' => '70000004',
            'email' => 'usuariotest2@test.com',
            'rol_id' => $this->gerenteRol->id,
            'foto' => 'default.jpg',
            'estado' => 'activo',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->patchJson("/api/usuarios/{$usuario->id}", [
            'estado' => 'inactivo',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.estado', 'inactivo');
    }

    public function test_puede_asignar_rol_a_usuario(): void
    {
        $usuario = Usuario::create([
            'nombre' => 'Usuario',
            'apellidos' => 'Test',
            'ci' => '66666666',
            'password' => Hash::make('password123'),
            'telefono' => '70000005',
            'email' => 'usuariotest3@test.com',
            'rol_id' => $this->gerenteRol->id,
            'foto' => 'default.jpg',
            'estado' => 'activo',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/usuarios/{$usuario->id}/assign-role", [
            'rol_id' => $this->adminRol->id,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('usuarios', [
            'id' => $usuario->id,
            'rol_id' => $this->adminRol->id,
        ]);
    }

    public function test_no_puede_eliminar_usuario_sin_permiso(): void
    {
        $usuario = Usuario::create([
            'nombre' => 'Usuario',
            'apellidos' => 'Eliminar',
            'ci' => '55555555',
            'password' => Hash::make('password123'),
            'telefono' => '70000006',
            'email' => 'usuarioeliminar@test.com',
            'rol_id' => $this->gerenteRol->id,
            'foto' => 'default.jpg',
            'estado' => 'activo',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/usuarios/{$usuario->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('usuarios', [
            'id' => $usuario->id,
        ]);
    }

    public function test_usuario_no_autenticado_no_puede_acceder(): void
    {
        $response = $this->getJson('/api/usuarios');

        $response->assertStatus(401);
    }
}
