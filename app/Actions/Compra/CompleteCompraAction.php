<?php

declare(strict_types=1);

namespace App\Actions\Compra;

use App\Models\Compra;
use App\Exceptions\ApiException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CompleteCompraAction
{
    /**
     * Complete a pending purchase.
     *
     * @param Compra $compra
     * @param array<string, mixed> $data
     * @return Compra
     */
    public function execute(Compra $compra, array $data = []): Compra
    {
        return DB::transaction(function () use ($compra, $data) {
            $this->validateCompletion($compra);

            $validatedData = $this->validate($data);

            // Update purchase status
            $compra->update([
                'estado' => 'completada',
                'pagado' => $validatedData['pagado'] ?? $compra->total,
                'saldo_pendiente' => max(0, $compra->total - ($validatedData['pagado'] ?? $compra->total)),
            ]);

            // Update caja balance
            $this->updateCajaBalance($compra, $validatedData['pagado'] ?? $compra->total);

            return $compra->fresh()->load(['proveedor', 'usuario', 'caja']);
        });
    }

    /**
     * Validate if purchase can be completed.
     *
     * @param Compra $compra
     * @throws ApiException
     */
    private function validateCompletion(Compra $compra): void
    {
        if ($compra->estado === 'completada') {
            throw ApiException::conflict(
                'La compra ya está completada',
                ['estado' => ['No se puede completar una compra ya finalizada']]
            );
        }

        if ($compra->estado === 'cancelada') {
            throw ApiException::conflict(
                'No se puede completar una compra cancelada',
                ['estado' => ['La compra está cancelada']]
            );
        }
    }

    /**
     * Validate completion data.
     *
     * @param array<string, mixed> $data
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
     *
     * @param Compra $compra
     * @param float $pagado
     */
    private function updateCajaBalance(Compra $compra, float $pagado): void
    {
        if ($pagado > 0) {
            $caja = $compra->caja;
            $caja->decrement('saldo_actual', $pagado);
        }
    }
}
