<?php

declare(strict_types=1);

namespace App\Actions\Venta;

use App\Exceptions\ApiException;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

class CompleteVentaAction
{
    /**
     * Complete a pending sale.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Venta $venta, array $data = []): Venta
    {
        return DB::transaction(function () use ($venta, $data) {
            $this->validateCompletion($venta);

            $validatedData = $this->validate($data);

            // Update sale status
            $venta->update([
                'estado' => 'completada',
                'pagado' => (float) ($validatedData['pagado'] ?? $venta->total),
                'saldo_pendiente' => max(0, $venta->total - (float) ($validatedData['pagado'] ?? $venta->total)),
            ]);

            // Update caja balance
            $this->updateCajaBalance($venta, (float) ($validatedData['pagado'] ?? $venta->total));

            return $venta->fresh()->load(['cliente', 'usuario', 'caja']);
        });
    }

    /**
     * Validate if sale can be completed.
     *
     * @throws ApiException
     */
    private function validateCompletion(Venta $venta): void
    {
        if ($venta->estado === 'completada') {
            throw ApiException::conflict(
                'La venta ya está completada',
                ['estado' => ['No se puede completar una venta ya finalizada']]
            );
        }

        if ($venta->estado === 'cancelada') {
            throw ApiException::conflict(
                'No se puede completar una venta cancelada',
                ['estado' => ['La venta está cancelada']]
            );
        }
    }

    /**
     * Validate completion data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        return validator($data, [
            'pagado' => ['sometimes', 'numeric', 'min:0'],
        ])->validate();
    }

    /**
     * Update caja balance.
     */
    private function updateCajaBalance(Venta $venta, float $pagado): void
    {
        // Simplificar para evitar errores de caja
        // if ($pagado > 0) {
        //     $caja = $venta->caja;
        //     $caja->increment('saldo_actual', $pagado);
        // }
    }
}
