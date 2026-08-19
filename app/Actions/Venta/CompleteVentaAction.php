<?php

declare(strict_types=1);

namespace App\Actions\Venta;

use App\Exceptions\ApiException;
use App\Models\Venta;
use App\Services\PagoService;
use Illuminate\Support\Facades\DB;

class CompleteVentaAction
{
    /**
     * Complete a pending sale.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Venta $venta, array $data = []): Venta
    {
        return DB::transaction(function () use ($venta, $data) {
            $venta = Venta::query()->lockForUpdate()->findOrFail($venta->id);
            $this->validateCompletion($venta);

            $validatedData = $this->validate($data);

            $total = (float) $venta->total;
            $pagado = (float) ($validatedData['pagado'] ?? $total);
            $pagado = max(0, min($pagado, $total));

            // Update sale status
            $venta->update([
                'estado' => 'completada',
                'pagado' => $pagado,
                'saldo_pendiente' => round($total - $pagado, 2),
            ]);

            if ($pagado > 0) {
                app(PagoService::class)->registrar($venta, $pagado, [
                    'tipo_pago' => $pagado >= $total ? 'total' : 'parcial',
                    'metodo_pago' => $venta->metodo_pago,
                ]);
            }

            $this->applyInventory($venta);

            return $venta->fresh()->load(['cliente', 'usuario', 'caja']);
        }, 3);
    }

    /**
     * Validate if sale can be completed.
     *
     * @throws ApiException
     */
    private function validateCompletion(Venta $venta): void
    {
        if ($venta->estado === 'completada') {
            throw ApiException::conflict(
                'La venta ya está completada',
                ['estado' => ['No se puede completar una venta ya finalizada']]
            );
        }

        if ($venta->estado === 'cancelada') {
            throw ApiException::conflict(
                'No se puede completar una venta cancelada',
                ['estado' => ['La venta está cancelada']]
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

    private function applyInventory(Venta $venta): void
    {
        $inventarioService = app(\App\Services\InventarioService::class);

        foreach ($venta->ventaProductos as $item) {
            $inventarioService->descontarParaVenta($venta, $item);
        }
    }
}
