<?php

declare(strict_types=1);

namespace App\Actions\Venta;

use App\Models\Venta;
use App\Models\VentaProducto;
use App\Models\Producto;
use App\Models\Caja;
use App\Exceptions\ApiException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UpdateVentaAction
{
    /**
     * Update an existing sale.
     *
     * @param Venta $venta
     * @param array<string, mixed> $data
     * @return Venta
     */
    public function execute(Venta $venta, array $data): Venta
    {
        return DB::transaction(function () use ($venta, $data) {
            $this->validateUpdateability($venta);

            $validatedData = $this->validate($data, $venta);

            // Update sale
            $venta->update($validatedData);

            return $venta->fresh()->load(['cliente', 'usuario', 'caja', 'ventaProductos.producto']);
        });
    }

    /**
     * Validate if sale can be updated.
     *
     * @param Venta $venta
     * @throws ApiException
     */
    private function validateUpdateability(Venta $venta): void
    {
        if ($venta->estado === 'cancelada') {
            throw ApiException::conflict(
                'No se puede modificar una venta cancelada',
                ['estado' => ['La venta ya está cancelada']]
            );
        }

        if ($venta->estado === 'completada') {
            throw ApiException::conflict(
                'No se puede modificar una venta completada',
                ['estado' => ['La venta ya está completada']]
            );
        }
    }

    /**
     * Validate the sale data for update.
     *
     * @param array<string, mixed> $data
     * @param Venta $venta
     * @return array<string, mixed>
     */
    private function validate(array $data, Venta $venta): array
    {
        return validator($data, [
            'cliente_id' => ['sometimes', 'exists:clientes,id'],
            'tipo_pago' => ['sometimes', Rule::in(['contado', 'credito'])],
            'metodo_pago' => ['sometimes', Rule::in(['efectivo', 'tarjeta', 'transferencia', 'cheque'])],
            'subtotal' => ['sometimes', 'numeric', 'min:0'],
            'impuesto' => ['sometimes', 'numeric', 'min:0'],
            'descuento' => ['sometimes', 'numeric', 'min:0'],
            'total' => ['sometimes', 'numeric', 'min:0'],
            'pagado' => ['sometimes', 'numeric', 'min:0'],
            'saldo_pendiente' => ['sometimes', 'numeric', 'min:0'],
            'estado' => ['sometimes', Rule::in(['pendiente', 'completada', 'cancelada'])],
            'observaciones' => ['sometimes', 'string', 'max:1000'],
        ])->validate();
    }
}
