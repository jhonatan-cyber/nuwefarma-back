<?php

declare(strict_types=1);

namespace App\Actions\Compra;

use App\Exceptions\ApiException;
use App\Models\Compra;
use Illuminate\Support\Facades\DB;

class DeleteCompraAction
{
    /**
     * Delete a purchase with business logic validation.
     * Reverts inventory if merchandise was already received.
     *
     * @throws ApiException
     */
    public function execute(Compra $compra): bool
    {
        return DB::transaction(function () use ($compra) {
            $compra = Compra::query()->lockForUpdate()->findOrFail($compra->id);
            $this->validateDeletion($compra);

            $compra->revertirInventario();

            $compra->productos()->delete();

            return $compra->delete();
        }, 3);
    }

    /**
     * Validate if purchase can be deleted.
     *
     * @throws ApiException
     */
    private function validateDeletion(Compra $compra): void
    {
        if (in_array($compra->estado, ['cancelada', 'devuelta'], true)) {
            throw ApiException::conflict(
                'No se puede eliminar la compra',
                ['estado' => ["La compra ya está {$compra->estado}"]]
            );
        }
    }
}