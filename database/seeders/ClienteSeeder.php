<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Cliente;

class ClienteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clientes = [
            [
                'ci' => '12345678',
                'nombre' => 'Juan Carlos',
                'apellido' => 'Pérez González',
                'telefono' => '70123456',
                'estado' => 'activo',
            ],
            [
                'ci' => '87654321',
                'nombre' => 'María Elena',
                'apellido' => 'Rodríguez López',
                'telefono' => '71234567',
                'estado' => 'activo',
            ],
            [
                'ci' => '11223344',
                'nombre' => 'Carlos Alberto',
                'apellido' => 'Mendoza Silva',
                'telefono' => '72345678',
                'estado' => 'activo',
            ],
            [
                'ci' => '44332211',
                'nombre' => 'Ana Sofía',
                'apellido' => 'Vargas Morales',
                'telefono' => '73456789',
                'estado' => 'activo',
            ],
            [
                'ci' => '55667788',
                'nombre' => 'Roberto',
                'apellido' => 'Fernández Castro',
                'telefono' => '74567890',
                'estado' => 'activo',
            ],
            [
                'ci' => '99887766',
                'nombre' => 'Lucía',
                'apellido' => 'Herrera Jiménez',
                'telefono' => '75678901',
                'estado' => 'activo',
            ],
            [
                'ci' => '13579246',
                'nombre' => 'Diego',
                'apellido' => 'Moreno Ruiz',
                'telefono' => '76789012',
                'estado' => 'activo',
            ],
            [
                'ci' => '24681357',
                'nombre' => 'Valentina',
                'apellido' => 'Torres Vega',
                'telefono' => '77890123',
                'estado' => 'activo',
            ],
            [
                'ci' => null,
                'nombre' => 'Cliente',
                'apellido' => 'Ocasional',
                'telefono' => null,
                'estado' => 'activo',
            ],
            [
                'ci' => '98765432',
                'nombre' => 'Patricia',
                'apellido' => 'Sánchez Delgado',
                'telefono' => '78901234',
                'estado' => 'inactivo',
            ],
        ];

        foreach ($clientes as $cliente) {
            Cliente::create($cliente);
        }
    }
}