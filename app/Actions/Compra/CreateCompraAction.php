<?php

declare(strict_types=1);

namespace App\Actions\Compra;

use App\Models\Caja;
use App\Models\Compra;
use App\Models\CompraProducto;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CreateCompraAction
{
    /**
     * Create a new purchase with products.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Compra
    {
        return DB::transaction(function () use ($data) {
            $validatedData = $this->validate($data);

            // Create purchase
            $compra = Compra::create([
                'proveedor_id' => $validatedData['proveedor_id'],
                'usuario_id' => $validatedData['usuario_id'],
                'caja_id' => $validatedData['caja_id'],
                'tipo_documento' => $validatedData['tipo_documento'],
                'numero_documento' => $validatedData['numero_documento'],
                'fecha_documento' => $validatedData['fecha_documento'],
                'subtotal' => $validatedData['subtotal'],
                'impuesto' => $validatedData['impuesto'],
                'descuento' => $validatedData['descuento'] ?? 0,
                'total' => $validatedData['total'],
                'pagado' => $validatedData['pagado'] ?? 0,
                'saldo_pendiente' => $validatedData['saldo_pendiente'] ?? 0,
                'estado' => $validatedData['estado'],
                'observaciones' => $validatedData['observaciones'] ?? null,
            ]);

            // Add products to purchase
            foreach ($validatedData['productos'] as $productoData) {
                $this->addProductToPurchase($compra, $productoData);
            }

            // Update caja balance
            $this->updateCajaBalance($compra);

            return $compra->load(['proveedor', 'usuario', 'caja', 'compraProductos.producto']);
        });
    }

    /**
     * Validate the purchase data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        return validator($data, [
            'proveedor_id' => ['required', 'exists:proveedors,id'],
            'usuario_id' => ['required', 'exists:usuarios,id'],
            'caja_id' => ['required', 'exists:cajas,id'],
            'tipo_documento' => ['required', Rule::in(['factura', 'boleta', 'nota_credito', 'guia_remision'])],
            'numero_documento' => ['required', 'string', 'max:100'],
            'fecha_documento' => ['required', 'date'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'impuesto' => ['required', 'numeric', 'min:0'],
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
            'productos.*.lote' => ['nullable', 'string', 'max:100'],
            'productos.*.fecha_vencimiento' => ['nullable', 'date'],
        ])->validate();
    }

    /**
     * Add product to purchase and update stock.
     *
     * @param  array<string, mixed>  $productoData
     */
    private function addProductToPurchase(Compra $compra, array $productoData): void
    {
        $producto = Producto::findOrFail($productoData['producto_id']);

        // Create purchase product
        CompraProducto::create([
            'compra_id' => $compra->id,
            'producto_id' => $producto->id,
            'cantidad' => $productoData['cantidad'],
            'precio_unitario' => $productoData['precio_unitario'],
            'descuento' => $productoData['descuento'] ?? 0,
            'subtotal' => ($productoData['precio_unitario'] * $productoData['cantidad']) - ($productoData['descuento'] ?? 0),
            'lote' => $productoData['lote'] ?? null,
            'fecha_vencimiento' => $productoData['fecha_vencimiento'] ?? null,
        ]);

        // Update product stock
        $producto->increment('stock_actual', $productoData['cantidad']);
    }

    /**
     * Update caja balance.
     */
    private function updateCajaBalance(Compra $compra): void
    {
        $caja = Caja::findOrFail($compra->caja_id);

        if ($compra->pagado > 0) {
            $caja->decrement('saldo_actual', $compra->pagado);
        }
    }
}
