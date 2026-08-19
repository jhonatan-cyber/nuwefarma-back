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
            $venta = Venta::query()->lockForUpdate()->findOrFail($venta->id);
            $this->validateDeletion($venta);

            if ($venta->estado === 'completada') {
                foreach ($venta->ventaProductos as $ventaProducto) {
                    $producto = $ventaProducto->producto()->lockForUpdate()->firstOrFail();
                    $producto->increment('stock_actual', $ventaProducto->cantidad);
                }

                if ($venta->pagado > 0) {
                    Caja::query()->lockForUpdate()->findOrFail($venta->caja_id)
                        ->decrement('saldo_actual', $venta->pagado);
                }
            }

            // Delete sale products first
            $venta->ventaProductos()->delete();

            // Delete sale
            return $venta->delete();
        }, 3);
    }

    /**
     * Validate if sale can be deleted.
     *
     * @throws ApiException
     */
    private function validateDeletion(Venta $venta): void
    {
        if (in_array($venta->estado, ['cancelada', 'devuelta'], true)) {
            throw ApiException::conflict(
                'No se puede eliminar la venta',
                ['estado' => ["La venta ya está {$venta->estado}"]]
            );
        }
    }
}
