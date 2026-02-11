<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Producto;
use App\Exceptions\ApiException;
use Illuminate\Support\Facades\DB;

class DeleteProductoAction
{
    /**
     * Delete a product with business logic validation.
     *
     * @param Producto $producto
     * @return bool
     * @throws ApiException
     */
    public function execute(Producto $producto): bool
    {
        return DB::transaction(function () use ($producto) {
            $this->validateDeletion($producto);

            return $producto->delete();
        });
    }

    /**
     * Validate if product can be deleted.
     *
     * @param Producto $producto
     * @throws ApiException
     */
    private function validateDeletion(Producto $producto): void
    {
        // Check if product has related records
        if ($producto->ventaProductos()->exists()) {
            throw ApiException::conflict(
                'No se puede eliminar el producto',
                ['ventas' => ['El producto tiene ventas asociadas']]
            );
        }

        if ($producto->compraProductos()->exists()) {
            throw ApiException::conflict(
                'No se puede eliminar el producto',
                ['compras' => ['El producto tiene compras asociadas']]
            );
        }

        if ($producto->lotes()->exists()) {
            throw ApiException::conflict(
                'No se puede eliminar el producto',
                ['lotes' => ['El producto tiene lotes asociados']]
            );
        }

        if ($producto->movimientosLote()->exists()) {
            throw ApiException::conflict(
                'No se puede eliminar el producto',
                ['movimientos' => ['El producto tiene movimientos de inventario asociados']]
            );
        }
    }
}
