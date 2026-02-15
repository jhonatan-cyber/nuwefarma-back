<?php

declare(strict_types=1);

namespace App\Actions\Venta;

use App\Exceptions\ApiException;
use App\Models\Caja;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

class DeleteVentaAction
{
    /**
     * Cancel/delete a sale with business logic validation.
     *
     * @throws ApiException
     */
    public function execute(Venta $venta): bool
    {
        return DB::transaction(function () use ($venta) {
            $this->validateDeletion($venta);

            // Restore stock for all products in the sale
            foreach ($venta->ventaProductos as $ventaProducto) {
                $producto = $ventaProducto->producto;
                $producto->increment('stock_actual', $ventaProducto->cantidad);
            }

            // Update caja balance if payment was made
            if ($venta->pagado > 0) {
                $caja = $venta->caja;
                $caja->decrement('saldo_actual', $venta->pagado);
            }

            // Delete sale products first
            $venta->ventaProductos()->delete();

            // Delete sale
            return $venta->delete();
        });
    }

    /**
     * Validate if sale can be deleted.
     *
     * @throws ApiException
     */
    private function validateDeletion(Venta $venta): void
    {
        if ($venta->estado === 'cancelada') {
            throw ApiException::conflict(
                'La venta ya está cancelada',
                ['estado' => ['No se puede eliminar una venta ya cancelada']]
            );
        }

        // Check if sale has related records that prevent deletion
        if ($venta->devoluciones()->exists()) {
            throw ApiException::conflict(
                'No se puede eliminar la venta',
                ['devoluciones' => ['La venta tiene devoluciones asociadas']]
            );
        }
    }
}
