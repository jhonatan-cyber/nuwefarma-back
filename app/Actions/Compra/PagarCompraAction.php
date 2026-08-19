<?php

declare(strict_types=1);

namespace App\Actions\Compra;

use App\Exceptions\ApiException;
use App\Models\Compra;
use App\Services\PagoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PagarCompraAction
{
    /**
     * Register a partial or total payment against a received purchase.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Compra $compra, array $data): Compra
    {
        return DB::transaction(function () use ($compra, $data) {
            $compra = Compra::query()->lockForUpdate()->findOrFail($compra->id);

            $validatedData = $this->validate($data);
            $monto = (float) $validatedData['monto'];

            $this->assertPagable($compra, $monto);

            $saldo = (float) $compra->saldo_pendiente;

            $compra->update([
                'pagado' => round((float) $compra->pagado + $monto, 2),
                'saldo_pendiente' => round($saldo - $monto, 2),
            ]);

            app(PagoService::class)->registrar($compra, $monto, [
                'tipo_pago' => $saldo - $monto <= 0.001 ? 'total' : 'parcial',
                'metodo_pago' => $validatedData['metodo_pago'] ?? $compra->metodo_pago,
            ]);

            return $compra->fresh()->load(['proveedor', 'usuario', 'sucursal', 'productos.producto']);
        }, 3);
    }

    /**
     * Validate if a payment can be registered against the purchase.
     *
     * @throws ApiException
     */
    private function assertPagable(Compra $compra, float $monto): void
    {
        if ($compra->estado !== 'recibida') {
            throw ApiException::conflict(
                'La compra no está recibida',
                ['estado' => ['Solo se pueden pagar compras recibidas']]
            );
        }

        if ((float) $compra->saldo_pendiente <= 0) {
            throw ApiException::conflict(
                'La compra ya está saldada',
                ['saldo_pendiente' => ['La compra no tiene saldo pendiente']]
            );
        }

        if ($monto > (float) $compra->saldo_pendiente) {
            throw ApiException::conflict(
                'El pago excede el saldo pendiente',
                ['monto' => ["El pago no puede superar el saldo pendiente de Bs {$compra->saldo_pendiente}"]]
            );
        }
    }

    /**
     * Validate payment data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        return validator($data, [
            'monto' => ['required', 'numeric', 'min:0.01'],
            'metodo_pago' => ['nullable', 'string', Rule::in(['efectivo', 'tarjeta', 'transferencia', 'cheque', 'mixto'])],
        ])->validate();
    }
}