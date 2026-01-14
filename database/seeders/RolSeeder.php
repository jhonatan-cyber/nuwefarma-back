<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        Rol::create([
            'id' => Str::uuid(),
            'nombre' => 'Administrador',
            'descripcion' => 'Acceso completo',
            'permiso_id' => [],
            'estado' => 'activo',
        ]);

        Rol::create([
            'id' => Str::uuid(),
            'nombre' => 'Cajero',
            'descripcion' => 'Acceso limitado',
            'permiso_id' => ['leer'],
            'estado' => 'activo',
        ]);

        Rol::create([
            'id' => Str::uuid(),
            'nombre' => 'Gerente',
            'descripcion' => 'Puede crear y editar',
            'permiso_id' => ['crear', 'editar', 'leer'],
            'estado' => 'activo',
        ]);
    }
}
