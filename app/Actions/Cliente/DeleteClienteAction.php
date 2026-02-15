<?php

declare(strict_types=1);

namespace App\Actions\Cliente;

use App\Exceptions\ApiException;
use App\Models\Cliente;

class DeleteClienteAction
{
    /**
     * Delete a client with business logic validation.
     *
     * @throws ApiException
     */
    public function execute(Cliente $cliente): bool
    {
        $this->validateDeletion($cliente);

        return $cliente->delete();
    }

    /**
     * Validate if client can be deleted.
     *
     * @throws ApiException
     */
    private function validateDeletion(Cliente $cliente): void
    {
        // Check if client has related records
        if ($cliente->ventas()->exists()) {
            throw ApiException::conflict(
                'No se puede eliminar el cliente',
                ['ventas' => ['El cliente tiene ventas asociadas']]
            );
        }

        if ($cliente->cotizaciones()->exists()) {
            throw ApiException::conflict(
                'No se puede eliminar el cliente',
                ['cotizaciones' => ['El cliente tiene cotizaciones asociadas']]
            );
        }

        // Check if client has outstanding debt
        if ($cliente->deudaPendiente() > 0) {
            throw ApiException::conflict(
                'No se puede eliminar el cliente',
                ['deuda' => ['El cliente tiene deuda pendiente']]
            );
        }
    }
}
