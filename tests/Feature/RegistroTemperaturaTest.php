<?php

namespace Tests\Feature;

use App\Models\RegistroTemperatura;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistroTemperaturaTest extends TestCase
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

    public function test_puede_registrar_lectura_dentro_de_rango(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/registros-temperatura', [
                'tipo_registro' => 'manual',
                'ubicacion' => 'Refrigerador 1',
                'temperatura' => 4.5,
                'humedad' => 60,
                'temp_minima_aceptable' => 2,
                'temp_maxima_aceptable' => 8,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.dentro_rango', true)
            ->assertJsonPath('data.ubicacion', 'Refrigerador 1');

        $this->assertDatabaseHas('registros_temperatura', [
            'ubicacion' => 'Refrigerador 1',
            'dentro_rango' => true,
        ]);
    }

    public function test_marca_fuera_de_rango_cuando_excede_el_limite(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/registros-temperatura', [
                'ubicacion' => 'Refrigerador Vacunas',
                'temperatura' => 11,
                'temp_minima_aceptable' => 2,
                'temp_maxima_aceptable' => 8,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.dentro_rango', false);

        $this->assertDatabaseHas('registros_temperatura', [
            'ubicacion' => 'Refrigerador Vacunas',
            'dentro_rango' => false,
        ]);
    }

    public function test_sin_rango_definido_siempre_es_valido(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/registros-temperatura', [
                'temperatura' => -5,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.dentro_rango', true);
    }

    public function test_puede_listar_registros(): void
    {
        RegistroTemperatura::create([
            'sucursal_id' => null,
            'tipo_registro' => 'manual',
            'ubicacion' => 'Refrigerador 1',
            'temperatura' => 4.0,
            'dentro_rango' => true,
            'registrado_en' => now(),
        ]);
        RegistroTemperatura::create([
            'sucursal_id' => null,
            'tipo_registro' => 'manual',
            'ubicacion' => 'Refrigerador 1',
            'temperatura' => 12.0,
            'dentro_rango' => false,
            'registrado_en' => now()->subHour(),
        ]);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/registros-temperatura')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 2);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/registros-temperatura?fuera_rango=1')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1);
    }

    public function test_puede_ver_alertas_de_temperatura(): void
    {
        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/registros-temperatura', [
                'ubicacion' => 'Refrigerador 1',
                'temperatura' => 12.0,
                'temp_minima_aceptable' => 2,
                'temp_maxima_aceptable' => 8,
            ])
            ->assertStatus(201);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/registros-temperatura/alertas');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_fuera_rango', 1);
    }
}