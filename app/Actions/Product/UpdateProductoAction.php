<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Producto;
use App\ValueObjects\ProductPrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UpdateProductoAction
{
    /**
     * Update an existing product.
     *
     * @param Producto $producto
     * @param array<string, mixed> $data
     * @return Producto
     */
    public function execute(Producto $producto, array $data): Producto
    {
        return DB::transaction(function () use ($producto, $data) {
            $validatedData = $this->validate($data, $producto);

            // Validate price if provided
            if (isset($validatedData['precio_compra']) || isset($validatedData['precio_venta'])) {
                $price = new ProductPrice(
                    $validatedData['precio_compra'] ?? $producto->precio_compra,
                    $validatedData['precio_venta'] ?? $producto->precio_venta,
                    $validatedData['impuesto'] ?? $producto->impuesto
                );
            }

            $producto->update($validatedData);

            return $producto->fresh()->load(['categoria', 'proveedor']);
        });
    }

    /**
     * Validate the product data for update.
     *
     * @param array<string, mixed> $data
     * @param Producto $producto
     * @return array<string, mixed>
     */
    private function validate(array $data, Producto $producto): array
    {
        return validator($data, [
            'nombre' => ['sometimes', 'string', 'max:255'],
            'categoria_id' => ['sometimes', 'exists:categorias,id'],
            'proveedor_id' => ['sometimes', 'exists:proveedors,id'],
            'codigo_barras' => ['sometimes', 'string', 'max:255', Rule::unique('productos', 'codigo_barras')->ignore($producto->id)],
            'sku' => ['sometimes', 'string', 'max:100', Rule::unique('productos', 'sku')->ignore($producto->id)],
            'codigo_interno' => ['sometimes', 'string', 'max:100'],
            'laboratorio' => ['sometimes', 'string', 'max:255'],
            'forma_farmaceutica' => ['sometimes', 'string', 'max:100'],
            'concentracion' => ['sometimes', 'string', 'max:100'],
            'presentacion' => ['sometimes', 'string', 'max:100'],
            'via_administracion' => ['sometimes', 'string', 'max:100'],
            'unidad_medida' => ['sometimes', 'string', 'max:50'],
            'fracciones_por_unidad' => ['sometimes', 'integer', 'min:1'],
            'permite_fraccionar' => ['sometimes', 'boolean'],
            'lote' => ['sometimes', 'string', 'max:100'],
            'fecha_vencimiento' => ['sometimes', 'date'],
            'registro_sanitario' => ['sometimes', 'string', 'max:255'],
            'refrigeracion_requerida' => ['sometimes', 'boolean'],
            'dias_para_alertar_vencimiento' => ['sometimes', 'integer', 'min:0'],
            'stock_actual' => ['sometimes', 'integer', 'min:0'],
            'stock_minimo' => ['sometimes', 'integer', 'min:0'],
            'stock_maximo' => ['sometimes', 'integer', 'min:0'],
            'precio_compra' => ['sometimes', 'numeric', 'min:0'],
            'precio_venta' => ['sometimes', 'numeric', 'min:0'],
            'impuesto' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'estado' => ['sometimes', Rule::in(['activo', 'inactivo', 'descontinuado'])],
        ])->validate();
    }
}
