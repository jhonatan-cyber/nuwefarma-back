<?php

declare(strict_types=1);

namespace App\Actions\Compra;

use App\Models\Compra;
use Illuminate\Support\Facades\DB;

class CancelarCompraAction
{
    /**
     * Cancel a purchase. Reverts inventory if merchandise was already received.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Compra $compra, array $data = []): Compra
    {
        return DB::transaction(function () use ($compra, $data) {
            $compra = Compra::query()->lockForUpdate()->findOrFail($compra->id);
            $validatedData = $this->validate($data);

            $compra->cancelar($validatedData['motivo'] ?? null);

            return $compra->fresh()->load(['proveedor', 'usuario', 'sucursal', 'productos.producto']);
        }, 3);
    }

    /**
     * Validate cancel data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        return validator($data, [
            'motivo' => ['nullable', 'string', 'max:1000'],
        ])->validate();
    }
}