<?php

namespace Database\Seeders;

use App\Models\Compra;
use App\Models\CompraProducto;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CompraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener datos necesarios
        $proveedores = Proveedor::where('estado', 'activo')->get();
        $usuarios = Usuario::get();
        $sucursales = Sucursal::where('estado', 'activo')->get();
        $productos = Producto::where('estado', 'activo')->get();

        if ($proveedores->isEmpty() || $usuarios->isEmpty() || $productos->isEmpty()) {
            $this->command->warn('No hay suficientes datos para crear compras. Asegúrate de tener proveedores, usuarios y productos.');

            return;
        }

        $estados = ['pendiente', 'recibida', 'cancelada'];
        $metodosPago = ['efectivo', 'tarjeta', 'transferencia', 'mixto'];

        // Crear 5 compras de ejemplo
        for ($i = 1; $i <= 5; $i++) {
            $proveedor = $proveedores->random();
            $usuario = $usuarios->random();
            $sucursal = $sucursales->isNotEmpty() ? $sucursales->random() : null;
            $estado = $estados[array_rand($estados)];
            $metodoPago = $metodosPago[array_rand($metodosPago)];

            $compra = Compra::create([
                'id' => Str::uuid(),
                'numero_compra' => 'C-'.str_pad($i, 8, '0', STR_PAD_LEFT),
                'subtotal' => 0, // Se calculará automáticamente
                'descuento' => rand(0, 50),
                'impuestos' => rand(10, 100) / 10, // Entre 1.0 y 10.0
                'total' => 0, // Se calculará automáticamente
                'estado' => $estado,
                'metodo_pago' => $metodoPago,
                'proveedor_id' => $proveedor->id,
                'usuario_id' => $usuario->id,
                'sucursal_id' => $sucursal?->id,
                'notas' => $this->getNotasEjemplo($i),
                'fecha_compra' => now()->subDays(rand(0, 30)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Agregar productos a la compra (2-5 productos por compra)
            $cantidadProductos = rand(2, 5);
            $productosSeleccionados = $productos->random($cantidadProductos);

            foreach ($productosSeleccionados as $producto) {
                $cantidad = rand(1, 10);
                $precioUnitario = $producto->precio * (rand(80, 120) / 100); // Precio de compra (80-120% del precio de venta)
                $descuentoUnitario = rand(0, 20);

                CompraProducto::create([
                    'id' => Str::uuid(),
                    'compra_id' => $compra->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioUnitario,
                    'descuento_unitario' => $descuentoUnitario,
                    'subtotal' => $cantidad * ($precioUnitario - $descuentoUnitario),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Recalcular totales
            $compra->calcularTotales();
        }

        $this->command->info('Se han creado 5 compras de ejemplo con sus productos.');
    }

    /**
     * Obtener notas de ejemplo para las compras
     */
    private function getNotasEjemplo(int $numero): string
    {
        $notas = [
            'Compra de productos farmacéuticos',
            'Reposición de inventario mensual',
            'Compra urgente por falta de stock',
            'Pedido especial del proveedor',
            'Compra de productos de temporada',
        ];

        return $notas[($numero - 1) % count($notas)];
    }
}
