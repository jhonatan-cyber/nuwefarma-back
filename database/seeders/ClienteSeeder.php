<?php

namespace Database\Seeders;

use App\Models\Cliente;
use Illuminate\Database\Seeder;

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
                'apellidos' => 'Pérez González',
                'telefono' => '70123456',
                'estado' => 'activo',
            ],
            [
                'ci' => '87654321',
                'nombre' => 'María Elena',
                'apellidos' => 'Rodríguez López',
                'telefono' => '71234567',
                'estado' => 'activo',
            ],
            [
                'ci' => '11223344',
                'nombre' => 'Carlos Alberto',
                'apellidos' => 'Mendoza Silva',
                'telefono' => '72345678',
                'estado' => 'activo',
            ],
            [
                'ci' => '44332211',
                'nombre' => 'Ana Sofía',
                'apellidos' => 'Vargas Morales',
                'telefono' => '73456789',
                'estado' => 'activo',
            ],
            [
                'ci' => '55667788',
                'nombre' => 'Roberto',
                'apellidos' => 'Fernández Castro',
                'telefono' => '74567890',
                'estado' => 'activo',
            ],
            [
                'ci' => '99887766',
                'nombre' => 'Lucía',
                'apellidos' => 'Herrera Jiménez',
                'telefono' => '75678901',
                'estado' => 'activo',
            ],
            [
                'ci' => '13579246',
                'nombre' => 'Diego',
                'apellidos' => 'Moreno Ruiz',
                'telefono' => '76789012',
                'estado' => 'activo',
            ],
            [
                'ci' => '24681357',
                'nombre' => 'Valentina',
                'apellidos' => 'Torres Vega',
                'telefono' => '77890123',
                'estado' => 'activo',
            ],
            [
                'ci' => null,
                'nombre' => 'Cliente',
                'apellidos' => 'Ocasional',
                'telefono' => null,
                'estado' => 'activo',
            ],
            [
                'ci' => '98765432',
                'nombre' => 'Patricia',
                'apellidos' => 'Sánchez Delgado',
                'telefono' => '78901234',
                'estado' => 'inactivo',
            ],
        ];

        foreach ($clientes as $cliente) {
            Cliente::create($cliente);
        }
    }
}
