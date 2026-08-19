<?php

declare(strict_types=1);

namespace App\Actions\Compra;

use App\Exceptions\ApiException;
use App\Models\Compra;
use Illuminate\Support\Facades\DB;

class RecibirCompraAction
{
    /**
     * Receive purchase merchandise (total or partial) generating lotes and kardex.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Compra $compra, array $data = []): Compra
    {
        return DB::transaction(function () use ($compra, $data) {
            $compra = Compra::query()->lockForUpdate()->findOrFail($compra->id);
            $validatedData = $this->validate($data);

            $compra->recibir($validatedData['items'] ?? []);

            return $compra->fresh()->load(['proveedor', 'usuario', 'sucursal', 'productos.producto']);
        }, 3);
    }

    /**
     * Validate receive data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        return validator($data, [
            'items' => ['sometimes', 'array'],
            'items.*.compra_producto_id' => ['required', 'string'],
            'items.*.cantidad_recibida' => ['required', 'integer', 'min:1'],
        ])->validate();
    }
}