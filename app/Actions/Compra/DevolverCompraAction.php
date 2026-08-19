<?php

declare(strict_types=1);

namespace App\Actions\Compra;

use App\Models\Compra;
use Illuminate\Support\Facades\DB;

class DevolverCompraAction
{
    /**
     * Return a received purchase to the supplier, reverting inventory.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Compra $compra, array $data = []): Compra
    {
        return DB::transaction(function () use ($compra, $data) {
            $compra = Compra::query()->lockForUpdate()->findOrFail($compra->id);
            $validatedData = $this->validate($data);

            $compra->devolver($validatedData['motivo'] ?? null);

            return $compra->fresh()->load(['proveedor', 'usuario', 'sucursal', 'productos.producto']);
        }, 3);
    }

    /**
     * Validate devolution data.
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