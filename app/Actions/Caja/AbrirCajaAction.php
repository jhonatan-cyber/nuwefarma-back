<?php

declare(strict_types=1);

namespace App\Actions\Caja;

use App\Exceptions\ApiException;
use App\Models\Caja;
use Illuminate\Support\Facades\DB;

class AbrirCajaAction
{
    /**
     * Open a cash register.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Caja $caja, array $data = []): Caja
    {
        return DB::transaction(function () use ($caja, $data) {
            $caja = Caja::query()->lockForUpdate()->findOrFail($caja->id);
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
        }, 3);
    }

    /**
     * Validate if cash register can be opened.
     *
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
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        return validator($data, [
            'saldo_inicial' => ['sometimes', 'numeric', 'min:0'],
        ])->validate();
    }
}
