<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Gasto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GastoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = Categoria::all();

        if ($categorias->isEmpty()) {
            $this->command->warn('No hay categorías. Ejecuta primero el CategoriaSeeder.');

            return;
        }

        $gastos = [
            [
                'nombre' => 'Compra de medicinas',
                'monto' => 450.50,
                'descripcion' => 'Compra de medicinas y antibióticos para inventario',
                'categoria' => 'Medicinas',
                'notas' => 'Proveedor: FarmaPlus',
                'fecha' => now()->subDays(5)->toDateString(),
            ],
            [
                'nombre' => 'Servicios de limpieza',
                'monto' => 200.00,
                'descripcion' => 'Limpieza y desinfección de las instalaciones',
                'categoria' => 'Servicios',
                'notas' => 'Servicio mensual',
                'fecha' => now()->subDays(3)->toDateString(),
            ],
            [
                'nombre' => 'Mantenimiento de equipos',
                'monto' => 350.00,
                'descripcion' => 'Mantenimiento preventivo de equipos médicos',
                'categoria' => 'Mantenimiento',
                'notas' => 'Contrato anual con TecnoMed',
                'fecha' => now()->subDays(7)->toDateString(),
            ],
            [
                'nombre' => 'Suministros de oficina',
                'monto' => 120.00,
                'descripcion' => 'Papelería y suministros para oficina administrativo',
                'categoria' => 'Suministros',
                'notas' => 'Compra en Staples',
                'fecha' => now()->subDays(2)->toDateString(),
            ],
            [
                'nombre' => 'Pago de servicios (agua)',
                'monto' => 85.00,
                'descripcion' => 'Factura de agua del mes actual',
                'categoria' => 'Servicios',
                'notas' => 'Factura: 2026-01',
                'fecha' => now()->subDays(1)->toDateString(),
            ],
        ];

        foreach ($gastos as $gasto) {
            Gasto::firstOrCreate(
                ['nombre' => $gasto['nombre'], 'fecha' => $gasto['fecha']],
                [
                    'id' => Str::uuid(),
                    'monto' => $gasto['monto'],
                    'descripcion' => $gasto['descripcion'],
                    'categoria' => $gasto['categoria'],
                    'notas' => $gasto['notas'],
                    'estado' => 'activo',
                ]
            );
        }

        $this->command->info('Gastos seed completado correctamente.');
    }
}
