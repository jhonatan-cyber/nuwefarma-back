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
        // Prevent deleting clients that already participate in sales history.
        if ($cliente->ventas()->exists()) {
            throw ApiException::conflict(
                'No se puede eliminar el cliente',
                ['ventas' => ['El cliente tiene ventas asociadas']]
            );
        }
    }
}
