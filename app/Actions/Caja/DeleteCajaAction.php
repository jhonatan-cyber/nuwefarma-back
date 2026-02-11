<?php

declare(strict_types=1);

namespace App\Actions\Caja;

use App\Models\Caja;
use App\Exceptions\ApiException;

class DeleteCajaAction
{
    /**
     * Delete a cash register with business logic validation.
     *
     * @param Caja $caja
     * @return bool
     * @throws ApiException
     */
    public function execute(Caja $caja): bool
    {
        $this->validateDeletion($caja);

        return $caja->delete();
    }

    /**
     * Validate if cash register can be deleted.
     *
     * @param Caja $caja
     * @throws ApiException
     */
    private function validateDeletion(Caja $caja): void
    {
        // Cannot delete if cash register is open
        if ($caja->estado === 'abierta') {
            throw ApiException::conflict(
                'No se puede eliminar una caja abierta',
                ['estado' => ['La caja debe estar cerrada para poder eliminarla']]
            );
        }

        // Check if cash register has related records
        if ($caja->ventas()->exists()) {
            throw ApiException::conflict(
                'No se puede eliminar la caja',
                ['ventas' => ['La caja tiene ventas asociadas']]
            );
        }

        if ($caja->compras()->exists()) {
            throw ApiException::conflict(
                'No se puede eliminar la caja',
                ['compras' => ['La caja tiene compras asociadas']]
            );
        }

        // Check if cash register has balance
        if ($caja->saldo_actual > 0) {
            throw ApiException::conflict(
                'No se puede eliminar la caja',
                ['saldo' => ['La caja tiene saldo pendiente']]
            );
        }
    }
}
