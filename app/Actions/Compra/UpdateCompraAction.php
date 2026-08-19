<?php

declare(strict_types=1);

namespace App\Actions\Compra;

use App\Exceptions\ApiException;
use App\Models\Compra;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UpdateCompraAction
{
    /**
     * Update an existing purchase.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Compra $compra, array $data): Compra
    {
        return DB::transaction(function () use ($compra, $data) {
            $compra = Compra::query()->lockForUpdate()->findOrFail($compra->id);
            $this->validateUpdateability($compra);

            $validatedData = $this->validate($data, $compra);

            foreach (['proveedor_id', 'usuario_id', 'sucursal_id'] as $field) {
                if (array_key_exists($field, $validatedData)) {
                    $validatedData[$field] = $validatedData[$field] ?: null;
                }
            }

            $compra->update($validatedData);
            $compra->calcularTotales();

            return $compra->fresh()->load(['proveedor', 'usuario', 'sucursal', 'productos.producto']);
        });
    }

    /**
     * Validate if purchase can be updated.
     *
     * @throws ApiException
     */
    private function validateUpdateability(Compra $compra): void
    {
        if (in_array($compra->estado, ['cancelada', 'devuelta'], true)) {
            throw ApiException::conflict(
                'No se puede modificar la compra',
                ['estado' => ["La compra ya está {$compra->estado}"]]
            );
        }

        if ($compra->estado === 'recibida') {
            throw ApiException::conflict(
                'No se puede modificar una compra recibida',
                ['estado' => ['La compra ya fue recibida y contabilizada']]
            );
        }
    }

    /**
     * Validate the purchase data for update.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data, Compra $compra): array
    {
        return validator($data, [
            'proveedor_id' => ['nullable', 'exists:proveedores,id'],
            'usuario_id' => ['nullable', 'exists:usuarios,id'],
            'sucursal_id' => ['nullable', 'exists:sucursals,id'],
            'metodo_pago' => ['sometimes', Rule::in(['efectivo', 'tarjeta', 'transferencia', 'mixto'])],
            'descuento' => ['sometimes', 'numeric', 'min:0'],
            'impuestos' => ['sometimes', 'numeric', 'min:0'],
            'notas' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ])->validate();
    }
}