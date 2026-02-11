<?php

declare(strict_types=1);

namespace App\Actions\Caja;

use App\Models\Caja;
use App\Exceptions\ApiException;
use Illuminate\Validation\Rule;

class UpdateCajaAction
{
    /**
     * Update an existing cash register.
     *
     * @param Caja $caja
     * @param array<string, mixed> $data
     * @return Caja
     */
    public function execute(Caja $caja, array $data): Caja
    {
        $this->validateUpdateability($caja, $data);

        $validatedData = $this->validate($data, $caja);

        $caja->update($validatedData);

        return $caja->fresh()->load(['sucursal', 'gerente']);
    }

    /**
     * Validate if cash register can be updated.
     *
     * @param Caja $caja
     * @param array<string, mixed> $data
     * @throws ApiException
     */
    private function validateUpdateability(Caja $caja, array $data): void
    {
        // Cannot change balance directly
        if (isset($data['saldo_actual'])) {
            throw ApiException::conflict(
                'No se puede modificar el saldo directamente',
                ['saldo_actual' => ['Use las operaciones de venta/compra para modificar el saldo']]
            );
        }

        // Cannot change sucursal if caja has transactions
        if (isset($data['sucursal_id']) && $caja->ventas()->exists()) {
            throw ApiException::conflict(
                'No se puede cambiar la sucursal',
                ['sucursal_id' => ['La caja tiene transacciones asociadas']]
            );
        }
    }

    /**
     * Validate the cash register data for update.
     *
     * @param array<string, mixed> $data
     * @param Caja $caja
     * @return array<string, mixed>
     */
    private function validate(array $data, Caja $caja): array
    {
        return validator($data, [
            'nombre' => ['sometimes', 'string', 'max:255'],
            'sucursal_id' => ['sometimes', 'exists:sucursals,id'],
            'gerente_id' => ['sometimes', 'exists:usuarios,id'],
            'saldo_inicial' => ['sometimes', 'numeric', 'min:0'],
            'descripcion' => ['sometimes', 'string', 'max:1000'],
            'estado' => ['sometimes', Rule::in(['abierta', 'cerrada'])],
        ])->validate();
    }
}
