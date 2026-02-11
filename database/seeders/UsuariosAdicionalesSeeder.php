<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\Usuario;
use App\Models\Sucursal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsuariosAdicionalesSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener todos los roles disponibles
        $roles = Rol::all()->keyBy('nombre');
        $sucursales = Sucursal::all();

        if ($roles->isEmpty()) {
            $this->command->warn('No hay roles disponibles. Ejecuta RolSeeder primero.');
            return;
        }

        if ($sucursales->isEmpty()) {
            $this->command->warn('No hay sucursales disponibles. Ejecuta SucursalSeeder primero.');
            return;
        }

        // === GERENTES (3 usuarios) ===
        if ($roles->has('Gerente')) {
            $gerenteRol = $roles['Gerente'];
            
            Usuario::create([
                'id' => Str::uuid(),
                'nombre' => 'Carlos',
                'apellidos' => 'Mendoza Rivera',
                'ci' => '11234567',
                'password' => Hash::make('gerente123'),
                'direccion' => 'Av. Ballivián #1234, Zona Sur',
                'telefono' => '71234567',
                'email' => 'carlos.mendoza@nuwefarma.com',
                'rol_id' => $gerenteRol->id,
                'sucursal_id' => $sucursales->random()->id,
                'sueldo' => '4500',
                'foto' => 'default.jpg',
                'estado' => 'activo',
            ]);

            Usuario::create([
                'id' => Str::uuid(),
                'nombre' => 'Ana',
                'apellidos' => 'Vargas Soliz',
                'ci' => '11345678',
                'password' => Hash::make('gerente123'),
                'direccion' => 'Calle Comercio #567, Centro',
                'telefono' => '71345678',
                'email' => 'ana.vargas@nuwefarma.com',
                'rol_id' => $gerenteRol->id,
                'sucursal_id' => $sucursales->random()->id,
                'sueldo' => '4800',
                'foto' => 'default.jpg',
                'estado' => 'activo',
            ]);

            Usuario::create([
                'id' => Str::uuid(),
                'nombre' => 'Roberto',
                'apellidos' => 'Chávez Morales',
                'ci' => '11456789',
                'password' => Hash::make('gerente123'),
                'direccion' => 'Av. América #890, Miraflores',
                'telefono' => '71456789',
                'email' => 'roberto.chavez@nuwefarma.com',
                'rol_id' => $gerenteRol->id,
                'sucursal_id' => $sucursales->random()->id,
                'sueldo' => '4600',
                'foto' => 'default.jpg',
                'estado' => 'activo',
            ]);
        }

        // === CAJEROS (5 usuarios) ===
        if ($roles->has('Cajero')) {
            $cajeroRol = $roles['Cajero'];
            
            $cajeros = [
                [
                    'nombre' => 'María',
                    'apellidos' => 'Quispe Mamani',
                    'ci' => '12234567',
                    'telefono' => '72234567',
                    'email' => 'maria.quispe@nuwefarma.com',
                    'direccion' => 'Calle Murillo #123, El Alto',
                    'sueldo' => '3200'
                ],
                [
                    'nombre' => 'Luis',
                    'apellidos' => 'Fernández Castro',
                    'ci' => '12345670',
                    'telefono' => '72345678',
                    'email' => 'luis.castro@nuwefarma.com',
                    'direccion' => 'Av. 6 de Agosto #456, San Miguel',
                    'sueldo' => '3100'
                ],
                [
                    'nombre' => 'Carmen',
                    'apellidos' => 'Rojas Pérez',
                    'ci' => '12456789',
                    'telefono' => '72456789',
                    'email' => 'carmen.rojas@nuwefarma.com',
                    'direccion' => 'Calle Sagárnaga #789, Centro',
                    'sueldo' => '3000'
                ],
                [
                    'nombre' => 'Pedro',
                    'apellidos' => 'Mamani Condori',
                    'ci' => '12567890',
                    'telefono' => '72567890',
                    'email' => 'pedro.mamani@nuwefarma.com',
                    'direccion' => 'Av. Arce #321, Sopocachi',
                    'sueldo' => '3300'
                ],
                [
                    'nombre' => 'Silvia',
                    'apellidos' => 'Torrez Gutiérrez',
                    'ci' => '12678901',
                    'telefono' => '72678901',
                    'email' => 'silvia.torrez@nuwefarma.com',
                    'direccion' => 'Calle Potosí #654, Villa Fátima',
                    'sueldo' => '3150'
                ]
            ];

            foreach ($cajeros as $cajero) {
                Usuario::create([
                    'id' => Str::uuid(),
                    'nombre' => $cajero['nombre'],
                    'apellidos' => $cajero['apellidos'],
                    'ci' => $cajero['ci'],
                    'password' => Hash::make('cajero123'),
                    'direccion' => $cajero['direccion'],
                    'telefono' => $cajero['telefono'],
                    'email' => $cajero['email'],
                    'rol_id' => $cajeroRol->id,
                    'sucursal_id' => $sucursales->random()->id,
                    'sueldo' => $cajero['sueldo'],
                    'foto' => 'default.jpg',
                    'estado' => 'activo',
                ]);
            }
        }

        // === USUARIOS (4 usuarios) ===
        if ($roles->has('Usuario')) {
            $usuarioRol = $roles['Usuario'];
            
            $usuarios = [
                [
                    'nombre' => 'Diego',
                    'apellidos' => 'Salinas Vega',
                    'ci' => '13234567',
                    'telefono' => '73234567',
                    'email' => 'diego.salinas@nuwefarma.com',
                    'direccion' => 'Av. Kantutani #987, Calacoto',
                    'sueldo' => '2800'
                ],
                [
                    'nombre' => 'Patricia',
                    'apellidos' => 'Herrera Luna',
                    'ci' => '13345678',
                    'telefono' => '73345678',
                    'email' => 'patricia.herrera@nuwefarma.com',
                    'direccion' => 'Calle Rosendo Gutiérrez #147, Sopocachi',
                    'sueldo' => '2900'
                ],
                [
                    'nombre' => 'Javier',
                    'apellidos' => 'Montaño Silva',
                    'ci' => '13456789',
                    'telefono' => '73456789',
                    'email' => 'javier.montano@nuwefarma.com',
                    'direccion' => 'Av. Hernando Siles #258, Obrajes',
                    'sueldo' => '2750'
                ],
                [
                    'nombre' => 'Verónica',
                    'apellidos' => 'Cáceres Ramos',
                    'ci' => '13567890',
                    'telefono' => '73567890',
                    'email' => 'veronica.caceres@nuwefarma.com',
                    'direccion' => 'Calle 21 de Calacoto #369, Calacoto',
                    'sueldo' => '2850'
                ]
            ];

            foreach ($usuarios as $usuario) {
                Usuario::create([
                    'id' => Str::uuid(),
                    'nombre' => $usuario['nombre'],
                    'apellidos' => $usuario['apellidos'],
                    'ci' => $usuario['ci'],
                    'password' => Hash::make('usuario123'),
                    'direccion' => $usuario['direccion'],
                    'telefono' => $usuario['telefono'],
                    'email' => $usuario['email'],
                    'rol_id' => $usuarioRol->id,
                    'sucursal_id' => $sucursales->random()->id,
                    'sueldo' => $usuario['sueldo'],
                    'foto' => 'default.jpg',
                    'estado' => 'activo',
                ]);
            }
        }

        // === EDITORES (3 usuarios) ===
        if ($roles->has('Editor')) {
            $editorRol = $roles['Editor'];
            
            $editores = [
                [
                    'nombre' => 'Sandra',
                    'apellidos' => 'Morales Ticona',
                    'ci' => '14234567',
                    'telefono' => '74234567',
                    'email' => 'sandra.morales@nuwefarma.com',
                    'direccion' => 'Av. Montes #741, San Pedro',
                    'sueldo' => '3800'
                ],
                [
                    'nombre' => 'Fernando',
                    'apellidos' => 'Aguilar Zenteno',
                    'ci' => '14345678',
                    'telefono' => '74345678',
                    'email' => 'fernando.aguilar@nuwefarma.com',
                    'direccion' => 'Calle Jaén #852, Centro',
                    'sueldo' => '3900'
                ],
                [
                    'nombre' => 'Mónica',
                    'apellidos' => 'Delgado Paredes',
                    'ci' => '14456789',
                    'telefono' => '74456789',
                    'email' => 'monica.delgado@nuwefarma.com',
                    'direccion' => 'Av. Camacho #963, Centro',
                    'sueldo' => '3700'
                ]
            ];

            foreach ($editores as $editor) {
                Usuario::create([
                    'id' => Str::uuid(),
                    'nombre' => $editor['nombre'],
                    'apellidos' => $editor['apellidos'],
                    'ci' => $editor['ci'],
                    'password' => Hash::make('editor123'),
                    'direccion' => $editor['direccion'],
                    'telefono' => $editor['telefono'],
                    'email' => $editor['email'],
                    'rol_id' => $editorRol->id,
                    'sucursal_id' => $sucursales->random()->id,
                    'sueldo' => $editor['sueldo'],
                    'foto' => 'default.jpg',
                    'estado' => 'activo',
                ]);
            }
        }

        // === USUARIOS INACTIVOS PARA PRUEBAS (2 usuarios) ===
        if ($roles->has('Cajero')) {
            $cajeroRol = $roles['Cajero'];
            
            Usuario::create([
                'id' => Str::uuid(),
                'nombre' => 'Raúl',
                'apellidos' => 'Mendez Flores',
                'ci' => '15234567',
                'password' => Hash::make('inactivo123'),
                'direccion' => 'Calle Ingavi #159, El Alto',
                'telefono' => '75234567',
                'email' => 'raul.mendez@nuwefarma.com',
                'rol_id' => $cajeroRol->id,
                'sucursal_id' => $sucursales->random()->id,
                'sueldo' => '3000',
                'foto' => 'default.jpg',
                'estado' => 'inactivo',
            ]);

            Usuario::create([
                'id' => Str::uuid(),
                'nombre' => 'Elena',
                'apellidos' => 'Vargas Choque',
                'ci' => '15345678',
                'password' => Hash::make('inactivo123'),
                'direccion' => 'Av. Buenos Aires #753, Villa Fátima',
                'telefono' => '75345678',
                'email' => 'elena.vargas@nuwefarma.com',
                'rol_id' => $cajeroRol->id,
                'sucursal_id' => $sucursales->random()->id,
                'sueldo' => '2950',
                'foto' => 'default.jpg',
                'estado' => 'inactivo',
            ]);
        }

        $this->command->info('✅ Usuarios adicionales creados exitosamente:');
        $this->command->info('   - 3 Gerentes');
        $this->command->info('   - 5 Cajeros');
        $this->command->info('   - 4 Usuarios');
        $this->command->info('   - 3 Editores');
        $this->command->info('   - 2 Usuarios inactivos');
        $this->command->info('📧 Contraseñas por rol:');
        $this->command->info('   - Gerentes: gerente123');
        $this->command->info('   - Cajeros: cajero123');
        $this->command->info('   - Usuarios: usuario123');
        $this->command->info('   - Editores: editor123');
        $this->command->info('   - Inactivos: inactivo123');
    }
}