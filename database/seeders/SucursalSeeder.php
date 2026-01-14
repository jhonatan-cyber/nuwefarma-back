<?php

namespace Database\Seeders;

use App\Models\Sucursal;
use Illuminate\Database\Seeder;

class SucursalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sucursales = [
            [
                'nombre' => 'Sucursal Centro',
                'direccion' => 'Carrera 7 No. 12-45',
                'ciudad' => 'Bogotá',
                'pais' => 'Colombia',
                'telefono' => '(1) 3456789',
                'email' => 'centro@nuwefarma.com',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Sucursal Norte',
                'direccion' => 'Calle 170 No. 15-20',
                'ciudad' => 'Bogotá',
                'pais' => 'Colombia',
                'telefono' => '(1) 6789012',
                'email' => 'norte@nuwefarma.com',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Sucursal Sur',
                'direccion' => 'Autopista Sur No. 50-25',
                'ciudad' => 'Bogotá',
                'pais' => 'Colombia',
                'telefono' => '(1) 2345678',
                'email' => 'sur@nuwefarma.com',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Sucursal Occidente',
                'direccion' => 'Avenida Ciudad de Cali No. 25-45',
                'ciudad' => 'Bogotá',
                'pais' => 'Colombia',
                'telefono' => '(1) 8901234',
                'email' => 'occidente@nuwefarma.com',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Sucursal Oriente',
                'direccion' => 'Calle 1 Este No. 10-15',
                'ciudad' => 'Bogotá',
                'pais' => 'Colombia',
                'telefono' => '(1) 5678901',
                'email' => 'oriente@nuwefarma.com',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Sucursal Medellín',
                'direccion' => 'Carrera 43A No. 14-20',
                'ciudad' => 'Medellín',
                'pais' => 'Colombia',
                'telefono' => '(4) 3456789',
                'email' => 'medellin@nuwefarma.com',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Sucursal Cali',
                'direccion' => 'Avenida 6 Norte No. 23-50',
                'ciudad' => 'Cali',
                'pais' => 'Colombia',
                'telefono' => '(2) 6789012',
                'email' => 'cali@nuwefarma.com',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Sucursal Barranquilla',
                'direccion' => 'Calle 85 No. 52-50',
                'ciudad' => 'Barranquilla',
                'pais' => 'Colombia',
                'telefono' => '(5) 3456789',
                'email' => 'barranquilla@nuwefarma.com',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Sucursal Cartagena',
                'direccion' => 'Avenida Pedro de Heredia No. 30-45',
                'ciudad' => 'Cartagena',
                'pais' => 'Colombia',
                'telefono' => '(5) 6789012',
                'email' => 'cartagena@nuwefarma.com',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Sucursal Bucaramanga',
                'direccion' => 'Carrera 27 No. 45-32',
                'ciudad' => 'Bucaramanga',
                'pais' => 'Colombia',
                'telefono' => '(7) 6345678',
                'email' => 'bucaramanga@nuwefarma.com',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Sucursal Pereira',
                'direccion' => 'Avenida Circunvalar No. 8-50',
                'ciudad' => 'Pereira',
                'pais' => 'Colombia',
                'telefono' => '(6) 3456789',
                'email' => 'pereira@nuwefarma.com',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Sucursal Manizales',
                'direccion' => 'Carrera 23 No. 62-15',
                'ciudad' => 'Manizales',
                'pais' => 'Colombia',
                'telefono' => '(6) 8901234',
                'email' => 'manizales@nuwefarma.com',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Sucursal Santa Marta',
                'direccion' => 'Calle 22 No. 3-50',
                'ciudad' => 'Santa Marta',
                'pais' => 'Colombia',
                'telefono' => '(5) 4234567',
                'email' => 'santamarta@nuwefarma.com',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Sucursal Villavicencio',
                'direccion' => 'Calle 40 No. 30-25',
                'ciudad' => 'Villavicencio',
                'pais' => 'Colombia',
                'telefono' => '(8) 6678901',
                'email' => 'villavicencio@nuwefarma.com',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Sucursal Pasto',
                'direccion' => 'Carrera 25 No. 18-30',
                'ciudad' => 'Pasto',
                'pais' => 'Colombia',
                'telefono' => '(2) 7234567',
                'email' => 'pasto@nuwefarma.com',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Sucursal Cúcuta',
                'direccion' => 'Avenida 0 No. 15-45',
                'ciudad' => 'Cúcuta',
                'pais' => 'Colombia',
                'telefono' => '(7) 5789012',
                'email' => 'cucuta@nuwefarma.com',
                'estado' => 'inactivo',
            ],
        ];

        foreach ($sucursales as $sucursal) {
            Sucursal::create($sucursal);
        }

        $this->command->info('✅ Sucursales creadas exitosamente');
    }
}
