<?php

namespace Database\Seeders;

use App\Models\Venta;
use App\Models\VentaProducto;
use App\Models\Cliente;
use App\Models\Usuario;
use App\Models\Sucursal;
use App\Models\Caja;
use App\Models\Producto;
use Illuminate\Database\Seeder;

class VentaSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Obtener datos relacionados
        $clientes = Cliente::all();
        $usuarios = Usuario::all();
        $sucursales = Sucursal::all();
        $cajas = Caja::all();
        $productos = Producto::where('estado', 'activo')->get();

        if ($productos->isEmpty()) {
            $this->command->warn('No hay productos activos para crear ventas');
            return;
        }

        $ventas = [
            [
                'subtotal' => 0, // Se calculará automáticamente
                'descuento' => 5.00,
                'impuestos' => 2.50,
                'total' => 0, // Se calculará automáticamente
                'estado' => 'completada',
                'metodo_pago' => 'efectivo',
                'cliente_id' => $clientes->isNotEmpty() ? $clientes->random()->id : null,
                'usuario_id' => $usuarios->isNotEmpty() ? $usuarios->random()->id : null,
                'sucursal_id' => $sucursales->isNotEmpty() ? $sucursales->random()->id : null,
                'caja_id' => $cajas->isNotEmpty() ? $cajas->random()->id : null,
                'notas' => 'Venta de productos farmacéuticos',
                'fecha_venta' => now()->subDays(2),
                'productos' => [
                    [
                        'producto_id' => $productos->random()->id,
                        'cantidad' => 2,
                        'precio_unitario' => 15.50,
                        'descuento_unitario' => 0.50,
                    ],
                    [
                        'producto_id' => $productos->random()->id,
                        'cantidad' => 1,
                        'precio_unitario' => 25.00,
                        'descuento_unitario' => 0.00,
                    ],
                ]
            ],
            [
                'subtotal' => 0,
                'descuento' => 0.00,
                'impuestos' => 1.80,
                'total' => 0,
                'estado' => 'completada',
                'metodo_pago' => 'tarjeta',
                'cliente_id' => $clientes->isNotEmpty() ? $clientes->random()->id : null,
                'usuario_id' => $usuarios->isNotEmpty() ? $usuarios->random()->id : null,
                'sucursal_id' => $sucursales->isNotEmpty() ? $sucursales->random()->id : null,
                'caja_id' => $cajas->isNotEmpty() ? $cajas->random()->id : null,
                'notas' => 'Compra de medicamentos recetados',
                'fecha_venta' => now()->subDays(1),
                'productos' => [
                    [
                        'producto_id' => $productos->random()->id,
                        'cantidad' => 3,
                        'precio_unitario' => 12.00,
                        'descuento_unitario' => 0.00,
                    ],
                ]
            ],
            [
                'subtotal' => 0,
                'descuento' => 2.00,
                'impuestos' => 3.20,
                'total' => 0,
                'estado' => 'pendiente',
                'metodo_pago' => 'transferencia',
                'cliente_id' => $clientes->isNotEmpty() ? $clientes->random()->id : null,
                'usuario_id' => $usuarios->isNotEmpty() ? $usuarios->random()->id : null,
                'sucursal_id' => $sucursales->isNotEmpty() ? $sucursales->random()->id : null,
                'caja_id' => $cajas->isNotEmpty() ? $cajas->random()->id : null,
                'notas' => 'Venta pendiente de confirmación',
                'fecha_venta' => now(),
                'productos' => [
                    [
                        'producto_id' => $productos->random()->id,
                        'cantidad' => 1,
                        'precio_unitario' => 45.00,
                        'descuento_unitario' => 2.00,
                    ],
                    [
                        'producto_id' => $productos->random()->id,
                        'cantidad' => 2,
                        'precio_unitario' => 8.50,
                        'descuento_unitario' => 0.00,
                    ],
                ]
            ],
            [
                'subtotal' => 0,
                'descuento' => 0.00,
                'impuestos' => 0.90,
                'total' => 0,
                'estado' => 'cancelada',
                'metodo_pago' => 'efectivo',
                'cliente_id' => null, // Venta sin cliente
                'usuario_id' => $usuarios->isNotEmpty() ? $usuarios->random()->id : null,
                'sucursal_id' => $sucursales->isNotEmpty() ? $sucursales->random()->id : null,
                'caja_id' => $cajas->isNotEmpty() ? $cajas->random()->id : null,
                'notas' => 'Venta cancelada por el cliente',
                'fecha_venta' => now()->subHours(3),
                'productos' => [
                    [
                        'producto_id' => $productos->random()->id,
                        'cantidad' => 1,
                        'precio_unitario' => 18.00,
                        'descuento_unitario' => 0.00,
                    ],
                ]
            ],
        ];

        foreach ($ventas as $ventaData) {
            // Extraer productos antes de crear la venta
            $productosData = $ventaData['productos'];
            unset($ventaData['productos']);

            // Generar número de venta
            $ventaData['numero_venta'] = Venta::generateNumeroVenta();

            // Crear la venta
            $venta = Venta::create($ventaData);

            // Crear los productos de la venta
            foreach ($productosData as $productoData) {
                $productoData['venta_id'] = $venta->id;
                VentaProducto::create($productoData);
            }

            // Calcular totales
            $venta->calcularTotales();
        }

        $this->command->info('✅ Ventas creadas exitosamente');
    }
}