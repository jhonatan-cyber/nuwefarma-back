<?php

declare(strict_types=1);

namespace App\Actions\Compra;

use App\Models\Compra;
use App\Models\CompraProducto;
use App\Models\Producto;
use App\Models\Caja;
use App\Exceptions\ApiException;
use Illuminate\Support\Facades\DB;

class DeleteCompraAction
{
    /**
     * Cancel/delete a purchase with business logic validation.
     *
     * @param Compra $compra
     * @return bool
     * @throws ApiException
     */
    public function execute(Compra $compra): bool
    {
        return DB::transaction(function () use ($compra) {
            $this->validateDeletion($compra);

            // Restore stock for all products in the purchase
            foreach ($compra->compraProductos as $compraProducto) {
                $producto = $compraProducto->producto;
                $producto->decrement('stock_actual', $compraProducto->cantidad);
            }

            // Update caja balance if payment was made
            if ($compra->pagado > 0) {
                $caja = $compra->caja;
                $caja->increment('saldo_actual', $compra->pagado);
            }

            // Delete purchase products first
            $compra->compraProductos()->delete();

            // Delete purchase
            return $compra->delete();
        });
    }

    /**
     * Validate if purchase can be deleted.
     *
     * @param Compra $compra
     * @throws ApiException
     */
    private function validateDeletion(Compra $compra): void
    {
        if ($compra->estado === 'cancelada') {
            throw ApiException::conflict(
                'La compra ya está cancelada',
                ['estado' => ['No se puede eliminar una compra ya cancelada']]
            );
        }

        // Check if purchase has related records that prevent deletion
        if ($compra->devoluciones()->exists()) {
            throw ApiException::conflict(
                'No se puede eliminar la compra',
                ['devoluciones' => ['La compra tiene devoluciones asociadas']]
            );
        }
    }
}
