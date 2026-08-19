<?php

namespace Tests\Concerns;

use App\Models\Rol;

trait CreatesAuthenticatedUser
{
    protected function createAuthenticatedUser(array $overrides = []): \App\Models\Usuario
    {
        // Crear rol de Administrador si no existe
        $rolAdministrador = \App\Models\Rol::where('nombre', 'Administrador')->first();
        if (! $rolAdministrador) {
            $rolAdministrador = \App\Models\Rol::factory()->create(['nombre' => 'Administrador']);
        }

        $userData = array_merge([
            'nombre' => 'Jhonatan',
            'apellidos' => 'Ancasi',
            'ci' => '10571705',
            'email' => 'jhonatanancasi@gmail.com',
            'password' => \Illuminate\Support\Facades\Hash::make('10571705'),
            'telefono' => '70000000',
            'foto' => 'default.jpg',
            'rol_id' => $rolAdministrador->id,
            'estado' => 'activo',
        ], $overrides);

        return \App\Models\Usuario::factory()->create($userData);
    }

    protected function authenticateUser(\App\Models\Usuario $user): string
    {
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => '10571705', // Usar la contraseña original
        ]);

        return $loginResponse['data']['token'];
    }
}
