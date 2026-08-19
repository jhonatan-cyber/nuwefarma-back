<?php

declare(strict_types=1);

namespace App\Actions\Compra;

use App\Exceptions\ApiException;
use App\Models\Compra;
use App\Services\PagoService;
use Illuminate\Support\Facades\DB;

class CompleteCompraAction
{
    /**
     * Complete a pending purchase (equivalent to full reception).
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Compra $compra, array $data = []): Compra
    {
        return DB::transaction(function () use ($compra, $data) {
            $compra = Compra::query()->lockForUpdate()->findOrFail($compra->id);
            $this->validateCompletion($compra);

            $validatedData = $this->validate($data);

            $compra->recibir();

            $total = (float) $compra->total;
            $pagado = (float) ($validatedData['pagado'] ?? $total);
            $pagado = max(0, min($pagado, $total));

            if ($pagado > 0) {
                $compra->update([
                    'pagado' => $pagado,
                    'saldo_pendiente' => round($total - $pagado, 2),
                ]);

                app(PagoService::class)->registrar($compra, $pagado, [
                    'tipo_pago' => $pagado >= $total ? 'total' : 'parcial',
                    'metodo_pago' => $compra->metodo_pago,
                ]);
            }

            return $compra->fresh()->load(['proveedor', 'usuario', 'sucursal', 'productos.producto']);
        }, 3);
    }

    /**
     * Validate if purchase can be completed.
     *
     * @throws ApiException
     */
    private function validateCompletion(Compra $compra): void
    {
        if ($compra->estado === 'recibida') {
            throw ApiException::conflict(
                'La compra ya está recibida',
                ['estado' => ['No se puede completar una compra ya recibida']]
            );
        }

        if (in_array($compra->estado, ['cancelada', 'devuelta'], true)) {
            throw ApiException::conflict(
                "No se puede completar una compra {$compra->estado}",
                ['estado' => ['La compra está finalizada']]
            );
        }
    }

    /**
     * Validate completion data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        return validator($data, [
            'pagado' => ['sometimes', 'numeric', 'min:0'],
        ])->validate();
    }
}