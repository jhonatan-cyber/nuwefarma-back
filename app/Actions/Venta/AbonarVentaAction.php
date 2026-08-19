<?php

declare(strict_types=1);

namespace App\Actions\Venta;

use App\Exceptions\ApiException;
use App\Models\Venta;
use App\Services\PagoService;
use Illuminate\Support\Facades\DB;

class AbonarVentaAction
{
    /**
     * Register a partial payment on a completed credit sale.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Venta $venta, array $data): Venta
    {
        return DB::transaction(function () use ($venta, $data) {
            $venta = Venta::query()->lockForUpdate()->findOrFail($venta->id);

            $validatedData = $this->validate($data);
            $monto = (float) $validatedData['monto'];

            $this->assertAbonable($venta, $monto);

            $venta->update([
                'pagado' => round((float) $venta->pagado + $monto, 2),
                'saldo_pendiente' => round((float) $venta->saldo_pendiente - $monto, 2),
            ]);

            app(PagoService::class)->registrar($venta, $monto, [
                'tipo_pago' => 'abono',
                'metodo_pago' => $validatedData['metodo_pago'] ?? $venta->metodo_pago,
            ]);

            return $venta->fresh()->load(['cliente', 'usuario', 'caja']);
        }, 3);
    }

    /**
     * Validate if a payment can be registered.
     *
     * @throws ApiException
     */
    private function assertAbonable(Venta $venta, float $monto): void
    {
        if ($venta->estado !== 'completada') {
            throw ApiException::conflict(
                'La venta no está completada',
                ['estado' => ['Solo se pueden abonar ventas completadas']]
            );
        }

        if ((float) $venta->saldo_pendiente <= 0) {
            throw ApiException::conflict(
                'La venta ya está saldada',
                ['saldo_pendiente' => ['La venta no tiene saldo pendiente']]
            );
        }

        if ($monto > (float) $venta->saldo_pendiente) {
            throw ApiException::conflict(
                'El abono excede el saldo pendiente',
                ['monto' => ["El abono no puede superar el saldo pendiente de Bs {$venta->saldo_pendiente}"]]
            );
        }
    }

    /**
     * Validate abono data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        return validator($data, [
            'monto' => ['required', 'numeric', 'min:0.01'],
            'metodo_pago' => ['nullable', 'string', 'in:efectivo,tarjeta,transferencia,cheque'],
        ])->validate();
    }
}