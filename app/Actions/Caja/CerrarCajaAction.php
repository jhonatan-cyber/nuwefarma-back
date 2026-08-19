<?php

declare(strict_types=1);

namespace App\Actions\Caja;

use App\Exceptions\ApiException;
use App\Models\Caja;
use Illuminate\Support\Facades\DB;

class CerrarCajaAction
{
    /**
     * Close a cash register.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Caja $caja, array $data = []): Caja
    {
        return DB::transaction(function () use ($caja, $data) {
            $caja = Caja::query()->lockForUpdate()->findOrFail($caja->id);
            $this->validateClosing($caja);

            $validatedData = $this->validate($data);

            // Update cash register
            $caja->update([
                'estado' => 'cerrada',
                'saldo_final' => $caja->saldo_actual,
                'fecha_cierre' => now(),
                'observaciones_cierre' => $validatedData['observaciones_cierre'] ?? null,
            ]);

            return $caja->fresh()->load(['sucursal', 'gerente']);
        }, 3);
    }

    /**
     * Validate if cash register can be closed.
     *
     * @throws ApiException
     */
    private function validateClosing(Caja $caja): void
    {
        if ($caja->estado === 'cerrada') {
            throw ApiException::conflict(
                'La caja ya está cerrada',
                ['estado' => ['La caja ya se encuentra cerrada']]
            );
        }
    }

    /**
     * Validate closing data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        return validator($data, [
            'observaciones_cierre' => ['nullable', 'string', 'max:1000'],
        ])->validate();
    }
}
