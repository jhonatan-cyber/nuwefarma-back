<?php

declare(strict_types=1);

namespace App\Actions\Compra;

use App\Models\Compra;
use App\Models\CompraProducto;
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

            $descuento = (float) ($validatedData['descuento'] ?? 0);
            $impuestos = (float) ($validatedData['impuestos'] ?? 0);
            $subtotal = collect($validatedData['productos'])->sum(
                fn ($item) => ((float) $item['precio_unitario'] - (float) ($item['descuento_unitario'] ?? 0)) * (int) $item['cantidad']
            );

            $compra = Compra::create([
                'proveedor_id' => $this->nullIfEmpty($validatedData['proveedor_id'] ?? null),
                'usuario_id' => $this->nullIfEmpty($validatedData['usuario_id'] ?? null) ?? auth()->id(),
                'sucursal_id' => $this->nullIfEmpty($validatedData['sucursal_id'] ?? null) ?? auth()->user()?->sucursal_id,
                'metodo_pago' => $validatedData['metodo_pago'] ?? 'efectivo',
                'descuento' => $descuento,
                'impuestos' => $impuestos,
                'subtotal' => $subtotal,
                'total' => $subtotal - $descuento + $impuestos,
                'estado' => 'pendiente',
                'notas' => $this->nullIfEmpty($validatedData['notas'] ?? null),
                'fecha_compra' => now(),
            ]);

            foreach ($validatedData['productos'] as $productoData) {
                $this->addProductToPurchase($compra, $productoData);
            }

            return $compra->load(['proveedor', 'usuario', 'sucursal', 'productos.producto']);
        }, 3);
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
            'proveedor_id' => ['nullable', 'exists:proveedores,id'],
            'usuario_id' => ['nullable', 'exists:usuarios,id'],
            'sucursal_id' => ['nullable', 'exists:sucursals,id'],
            'metodo_pago' => ['nullable', Rule::in(['efectivo', 'tarjeta', 'transferencia', 'mixto'])],
            'descuento' => ['nullable', 'numeric', 'min:0'],
            'impuestos' => ['nullable', 'numeric', 'min:0'],
            'notas' => ['nullable', 'string', 'max:1000'],
            'productos' => ['required', 'array', 'min:1'],
            'productos.*.producto_id' => ['required', 'exists:productos,id'],
            'productos.*.lote_id' => ['nullable', 'exists:lotes,id'],
            'productos.*.numero_lote' => ['nullable', 'string', 'max:100'],
            'productos.*.cantidad' => ['required', 'integer', 'min:1'],
            'productos.*.precio_unitario' => ['required', 'numeric', 'min:0'],
            'productos.*.descuento_unitario' => ['nullable', 'numeric', 'min:0'],
            'productos.*.fecha_vencimiento' => ['nullable', 'date'],
        ])->validate();
    }

    /**
     * Add product to purchase.
     *
     * @param  array<string, mixed>  $productoData
     */
    private function addProductToPurchase(Compra $compra, array $productoData): void
    {
        CompraProducto::create([
            'compra_id' => $compra->id,
            'producto_id' => $productoData['producto_id'],
            'lote_id' => $this->nullIfEmpty($productoData['lote_id'] ?? null),
            'numero_lote' => $this->nullIfEmpty($productoData['numero_lote'] ?? null),
            'fecha_vencimiento' => $this->nullIfEmpty($productoData['fecha_vencimiento'] ?? null),
            'cantidad' => $productoData['cantidad'],
            'precio_unitario' => $productoData['precio_unitario'],
            'descuento_unitario' => $productoData['descuento_unitario'] ?? 0,
        ]);
    }

    private function nullIfEmpty(mixed $value): mixed
    {
        return $value === '' || $value === null ? null : $value;
    }
}