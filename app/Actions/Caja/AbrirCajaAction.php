<?php

declare(strict_types=1);

namespace App\Actions\Caja;

use App\Models\Caja;
use App\Exceptions\ApiException;
use Illuminate\Validation\Rule;

class AbrirCajaAction
{
    /**
     * Open a cash register.
     *
     * @param Caja $caja
     * @param array<string, mixed> $data
     * @return Caja
     */
    public function execute(Caja $caja, array $data = []): Caja
    {
        $this->validateOpening($caja);

        $validatedData = $this->validate($data);

        // Update cash register
        $caja->update([
            'estado' => 'abierta',
            'saldo_inicial' => $validatedData['saldo_inicial'] ?? $caja->saldo_actual,
            'saldo_actual' => $validatedData['saldo_inicial'] ?? $caja->saldo_actual,
            'fecha_apertura' => now(),
        ]);

        return $caja->fresh()->load(['sucursal', 'gerente']);
    }

    /**
     * Validate if cash register can be opened.
     *
     * @param Caja $caja
     * @throws ApiException
     */
    private function validateOpening(Caja $caja): void
    {
        if ($caja->estado === 'abierta') {
            throw ApiException::conflict(
                'La caja ya está abierta',
                ['estado' => ['La caja ya se encuentra abierta']]
            );
        }
    }

    /**
     * Validate opening data.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        return validator($data, [
            'saldo_inicial' => ['sometimes', 'numeric', 'min:0'],
        ])->validate();
    }
}
