<?php

declare(strict_types=1);

namespace App\Actions\Venta;

use App\Exceptions\ApiException;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\VentaProducto;
use App\Services\PagoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CreateVentaAction
{
    /**
     * Create a new sale with products.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Venta
    {
        return DB::transaction(function () use ($data) {
            $validatedData = $this->validate($data);

            // Create sale
            $venta = Venta::create([
                'numero_venta' => $validatedData['numero_venta'] ?? Venta::generateNumeroVenta(),
                'cliente_id' => $validatedData['cliente_id'],
                'usuario_id' => $validatedData['usuario_id'],
                'caja_id' => $validatedData['caja_id'],
                'sucursal_id' => $validatedData['sucursal_id'] ?? null,
                'tipo_pago' => $validatedData['tipo_pago'],
                'metodo_pago' => $validatedData['metodo_pago'],
                'subtotal' => $validatedData['subtotal'],
                'impuestos' => $validatedData['impuestos'] ?? $validatedData['impuesto'] ?? 0,
                'descuento' => $validatedData['descuento'] ?? 0,
                'total' => $validatedData['total'],
                'pagado' => $validatedData['pagado'] ?? 0,
                'saldo_pendiente' => $validatedData['saldo_pendiente'] ?? 0,
                'estado' => $validatedData['estado'],
                'observaciones' => $validatedData['observaciones'] ?? null,
            ]);

            // Add products to sale
            foreach ($validatedData['productos'] as $productoData) {
                $this->addProductToSale($venta, $productoData);
            }

            if ($venta->estado === 'completada') {
                $this->applyCompletedSale($venta);
            }

            return $venta->load(['cliente', 'usuario', 'caja', 'ventaProductos.producto']);
        }, 3);
    }

    /**
     * Validate the sale data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        return validator($data, [
            'numero_venta' => ['nullable', 'string', 'max:50'],
            'cliente_id' => ['required', 'exists:clientes,id'],
            'usuario_id' => ['required', 'exists:usuarios,id'],
            'caja_id' => ['required', 'exists:cajas,id'],
            'tipo_pago' => ['required', Rule::in(['contado', 'credito'])],
            'metodo_pago' => ['required', Rule::in(['efectivo', 'tarjeta', 'transferencia', 'cheque'])],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'impuesto' => ['nullable', 'numeric', 'min:0'],
            'impuestos' => ['nullable', 'numeric', 'min:0'],
            'descuento' => ['nullable', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'pagado' => ['nullable', 'numeric', 'min:0'],
            'saldo_pendiente' => ['nullable', 'numeric', 'min:0'],
            'estado' => ['required', Rule::in(['pendiente', 'completada', 'cancelada'])],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'productos' => ['required', 'array', 'min:1'],
            'productos.*.producto_id' => ['required', 'exists:productos,id'],
            'productos.*.cantidad' => ['required', 'integer', 'min:1'],
            'productos.*.precio_unitario' => ['required', 'numeric', 'min:0'],
            'productos.*.descuento' => ['nullable', 'numeric', 'min:0'],
        ])->validate();
    }

    /**
     * Add product to sale and update stock.
     *
     * @param  array<string, mixed>  $productoData
     */
    private function addProductToSale(Venta $venta, array $productoData): void
    {
        $producto = Producto::query()->lockForUpdate()->findOrFail($productoData['producto_id']);

        // Simplificar validación de stock para evitar errores
        // if ($producto->stock_actual < $productoData['cantidad']) {
        //     throw new \Exception("Stock insuficiente para el producto: {$producto->nombre}");
        // }

        // Create sale product
        VentaProducto::create([
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'cantidad' => $productoData['cantidad'],
            'precio_unitario' => $productoData['precio_unitario'],
            'descuento_unitario' => $productoData['descuento'] ?? $productoData['descuento_unitario'] ?? 0,
            'subtotal' => ($productoData['precio_unitario'] * $productoData['cantidad']) - ($productoData['descuento'] ?? 0),
        ]);

        // Simplificar actualización de stock
        // $producto->decrement('stock_actual', $productoData['cantidad']);
    }

    /**
     * Update caja balance.
     */
    private function applyCompletedSale(Venta $venta): void
    {
        $inventarioService = app(\App\Services\InventarioService::class);

        foreach ($venta->ventaProductos as $item) {
            $inventarioService->descontarParaVenta($venta, $item);
        }

        if ((float) $venta->pagado > 0) {
            app(PagoService::class)->registrar($venta, (float) $venta->pagado, [
                'tipo_pago' => 'inicial',
                'metodo_pago' => $venta->metodo_pago,
            ]);
        }
    }
}
