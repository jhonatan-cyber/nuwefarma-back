<?php

declare(strict_types=1);

namespace App\Actions\Venta;

use App\Models\Venta;
use Illuminate\Support\Facades\DB;

class CancelarVentaAction
{
    /**
     * Cancel a sale. Reverts inventory and caja if it was already completed.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Venta $venta, array $data = []): Venta
    {
        return DB::transaction(function () use ($venta, $data) {
            $venta = Venta::query()->lockForUpdate()->findOrFail($venta->id);
            $validatedData = $this->validate($data);

            $venta->cancelar($validatedData['motivo'] ?? null);

            return $venta->fresh()->load(['cliente', 'usuario', 'sucursal', 'caja', 'ventaProductos.producto']);
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
            'motivo' => ['nullable', 'string', 'max:255'],
        ])->validate();
    }
}