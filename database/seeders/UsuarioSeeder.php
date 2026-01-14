<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener roles
        $adminRol = Rol::where('nombre', 'Administrador')->first();
        $usuarioRol = Rol::where('nombre', 'Cajero')->first();
        $editorRol = Rol::where('nombre', 'Gerente')->first();

        if (!$adminRol || !$usuarioRol || !$editorRol) {
            $this->command->warn('Faltan roles. Asegúrate de ejecutar RolSeeder primero.');
            return;
        }

        // Usuarios Administradores
        Usuario::create([
            'id' => Str::uuid(),
            'nombre' => 'Jhonatan',
            'apellidos' => 'Ancasi Flores',
            'ci' => '10571705',
            'password' => Hash::make('10571705'),
            'direccion' => 'Av German Bush',
            'telefono' => '72419112',
            'email' => 'jhonatanancasi@gmail.com',
            'rol_id' => $adminRol->id,
            'sueldo' => '5000',
            'foto' => 'default.jpg',
            'estado' => 'activo',
        ]);

        Usuario::create([
            'id' => Str::uuid(),
            'nombre' => 'María',
            'apellidos' => 'González Pérez',
            'ci' => '12345678',
            'password' => Hash::make('12345678'),
            'direccion' => 'Calle 10 No. 20-30',
            'telefono' => '72345678',
            'email' => 'maria.gonzalez@nuwefarma.com',
            'rol_id' => $adminRol->id,
            'sueldo' => '5200',
            'foto' => 'default.jpg',
            'estado' => 'activo',
        ]);

        // Usuarios Editores
        Usuario::create([
            'id' => Str::uuid(),
            'nombre' => 'Carlos',
            'apellidos' => 'Rodríguez López',
            'ci' => '23456789',
            'password' => Hash::make('23456789'),
            'direccion' => 'Carrera 15 No. 25-40',
            'telefono' => '73456789',
            'email' => 'carlos.rodriguez@nuwefarma.com',
            'rol_id' => $editorRol->id,
            'sueldo' => '4000',
            'foto' => 'default.jpg',
            'estado' => 'activo',
        ]);

        Usuario::create([
            'id' => Str::uuid(),
            'nombre' => 'Ana',
            'apellidos' => 'Martínez Silva',
            'ci' => '34567890',
            'password' => Hash::make('34567890'),
            'direccion' => 'Avenida 20 No. 15-25',
            'telefono' => '74567890',
            'email' => 'ana.martinez@nuwefarma.com',
            'rol_id' => $editorRol->id,
            'sueldo' => '4200',
            'foto' => 'default.jpg',
            'estado' => 'activo',
        ]);

        Usuario::create([
            'id' => Str::uuid(),
            'nombre' => 'Luis',
            'apellidos' => 'Fernández Castro',
            'ci' => '45678901',
            'password' => Hash::make('45678901'),
            'direccion' => 'Calle 30 No. 10-20',
            'telefono' => '75678901',
            'email' => 'luis.fernandez@nuwefarma.com',
            'rol_id' => $editorRol->id,
            'sueldo' => '3800',
            'foto' => 'default.jpg',
            'estado' => 'activo',
        ]);

        // Usuarios con rol Usuario
        Usuario::create([
            'id' => Str::uuid(),
            'nombre' => 'Laura',
            'apellidos' => 'Sánchez Moreno',
            'ci' => '56789012',
            'password' => Hash::make('56789012'),
            'direccion' => 'Carrera 8 No. 12-18',
            'telefono' => '76789012',
            'email' => 'laura.sanchez@nuwefarma.com',
            'rol_id' => $usuarioRol->id,
            'sueldo' => '3000',
            'foto' => 'default.jpg',
            'estado' => 'activo',
        ]);

        Usuario::create([
            'id' => Str::uuid(),
            'nombre' => 'Diego',
            'apellidos' => 'Torres Ramírez',
            'ci' => '67890123',
            'password' => Hash::make('67890123'),
            'direccion' => 'Avenida 5 No. 22-35',
            'telefono' => '77890123',
            'email' => 'diego.torres@nuwefarma.com',
            'rol_id' => $usuarioRol->id,
            'sueldo' => '3200',
            'foto' => 'default.jpg',
            'estado' => 'activo',
        ]);

        Usuario::create([
            'id' => Str::uuid(),
            'nombre' => 'Patricia',
            'apellidos' => 'Vargas Mendoza',
            'ci' => '78901234',
            'password' => Hash::make('78901234'),
            'direccion' => 'Calle 18 No. 8-15',
            'telefono' => '78901234',
            'email' => 'patricia.vargas@nuwefarma.com',
            'rol_id' => $usuarioRol->id,
            'sueldo' => '2900',
            'foto' => 'default.jpg',
            'estado' => 'activo',
        ]);

        Usuario::create([
            'id' => Str::uuid(),
            'nombre' => 'Roberto',
            'apellidos' => 'Díaz Jiménez',
            'ci' => '89012345',
            'password' => Hash::make('89012345'),
            'direccion' => 'Carrera 12 No. 30-42',
            'telefono' => '79012345',
            'email' => 'roberto.diaz@nuwefarma.com',
            'rol_id' => $usuarioRol->id,
            'sueldo' => '3100',
            'foto' => 'default.jpg',
            'estado' => 'activo',
        ]);

        // Usuario inactivo para pruebas
        Usuario::create([
            'id' => Str::uuid(),
            'nombre' => 'Sandra',
            'apellidos' => 'Ruiz Herrera',
            'ci' => '90123456',
            'password' => Hash::make('90123456'),
            'direccion' => 'Avenida 12 No. 45-60',
            'telefono' => '70123456',
            'email' => 'sandra.ruiz@nuwefarma.com',
            'rol_id' => $usuarioRol->id,
            'sueldo' => '2800',
            'foto' => 'default.jpg',
            'estado' => 'inactivo',
        ]);

        $this->command->info('✅ Usuarios creados exitosamente');
    }
}
