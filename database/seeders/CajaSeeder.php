<?php

namespace Database\Seeders;

use App\Models\Caja;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class CajaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $usuarios = Usuario::all();
        $sucursales = Sucursal::all();

        $cajas = [
            [
                'nombre' => 'Caja Principal',
                'descripcion' => 'Caja principal de la farmacia',
                'saldo_inicial' => 1000.00,
                'saldo_actual' => 1250.50,
                'total_ingresos' => 500.50,
                'total_egresos' => 250.00,
                'fecha_apertura' => now()->subDays(1)->toDateString(),
                'estado' => 'abierta',
                'usuario_id' => $usuarios->where('rol.nombre', 'Cajero')->first()?->id,
                'sucursal_id' => $sucursales->first()?->id,
                'notas' => 'Caja principal para ventas diarias',
            ],
            [
                'nombre' => 'Caja Secundaria',
                'descripcion' => 'Caja de apoyo para horarios pico',
                'saldo_inicial' => 500.00,
                'saldo_actual' => 750.25,
                'total_ingresos' => 300.25,
                'total_egresos' => 50.00,
                'fecha_apertura' => now()->toDateString(),
                'estado' => 'abierta',
                'usuario_id' => $usuarios->where('rol.nombre', 'Vendedor')->first()?->id,
                'sucursal_id' => $sucursales->first()?->id,
                'notas' => 'Caja de apoyo para ventas rápidas',
            ],
            [
                'nombre' => 'Caja Express',
                'descripcion' => 'Caja para ventas express y entregas',
                'saldo_inicial' => 200.00,
                'saldo_actual' => 180.00,
                'total_ingresos' => 80.00,
                'total_egresos' => 100.00,
                'fecha_apertura' => now()->subDays(2)->toDateString(),
                'fecha_cierre' => now()->subDays(1)->toDateString(),
                'estado' => 'cerrada',
                'usuario_id' => $usuarios->where('rol.nombre', 'Vendedor')->skip(1)->first()?->id,
                'sucursal_id' => $sucursales->skip(1)->first()?->id,
                'notas' => 'Caja especializada en entregas a domicilio',
            ],
            [
                'nombre' => 'Caja Nocturna',
                'descripcion' => 'Caja para turnos nocturnos',
                'saldo_inicial' => 300.00,
                'saldo_actual' => 300.00,
                'total_ingresos' => 0.00,
                'total_egresos' => 0.00,
                'fecha_apertura' => now()->addDays(1)->toDateString(),
                'estado' => 'suspendida',
                'usuario_id' => $usuarios->where('rol.nombre', 'Cajero')->skip(1)->first()?->id,
                'sucursal_id' => $sucursales->first()?->id,
                'notas' => 'Caja preparada para turno nocturno',
            ],
        ];

        foreach ($cajas as $cajaData) {
            $cajaData['numero_caja'] = Caja::generateNumeroCaja();
            Caja::create($cajaData);
        }
    }
}
