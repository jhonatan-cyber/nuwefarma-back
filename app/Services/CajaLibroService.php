<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Caja;
use App\Models\MovimientoCaja;

class CajaLibroService
{
    /**
     * Registrar un ingreso de efectivo en la caja.
     */
    public function ingreso(
        string $cajaId,
        float $monto,
        string $origen,
        ?string $documentoTipo = null,
        ?string $documentoId = null,
        ?string $documentoNumero = null,
        ?string $concepto = null
    ): ?MovimientoCaja {
        return $this->registrar(MovimientoCaja::INGRESO, $cajaId, $monto, $origen, $documentoTipo, $documentoId, $documentoNumero, $concepto);
    }

    /**
     * Registrar un egreso de efectivo de la caja.
     */
    public function egreso(
        string $cajaId,
        float $monto,
        string $origen,
        ?string $documentoTipo = null,
        ?string $documentoId = null,
        ?string $documentoNumero = null,
        ?string $concepto = null
    ): ?MovimientoCaja {
        return $this->registrar(MovimientoCaja::EGRESO, $cajaId, $monto, $origen, $documentoTipo, $documentoId, $documentoNumero, $concepto);
    }

    /**
     * Registrar un movimiento de caja con candado pesimista y guardar el
     * saldo posterior al movimiento para dejar una traza auditable.
     */
    public function registrar(
        string $tipo,
        string $cajaId,
        float $monto,
        string $origen,
        ?string $documentoTipo = null,
        ?string $documentoId = null,
        ?string $documentoNumero = null,
        ?string $concepto = null
    ): ?MovimientoCaja {
        if ($monto <= 0 || ! $cajaId) {
            return null;
        }

        $caja = Caja::query()->lockForUpdate()->find($cajaId);

        if (! $caja) {
            return null;
        }

        // Guarda de operación cerrada: una caja cerrada solo admite el ajuste
        // derivado de la conciliación de un arqueo, nunca movimientos operativos.
        if ($caja->estado === 'cerrada' && $origen !== MovimientoCaja::ORIGEN_AJUSTE_ARQUEO) {
            throw ApiException::conflict(
                "La caja {$caja->numero_caja} está cerrada y no admite movimientos",
                ['caja' => ['Debe abrir la caja para registrar operaciones']]
            );
        }

        if ($tipo === MovimientoCaja::INGRESO) {
            $caja->increment('saldo_actual', $monto);
            $caja->increment('total_ingresos', $monto);
        } else {
            $caja->decrement('saldo_actual', $monto);
            $caja->increment('total_egresos', $monto);
        }

        return MovimientoCaja::create([
            'caja_id' => $cajaId,
            'tipo' => $tipo,
            'origen' => $origen,
            'documento_tipo' => $documentoTipo,
            'documento_id' => $documentoId,
            'documento_numero' => $documentoNumero,
            'monto' => $monto,
            'saldo_despues' => (float) $caja->fresh()->saldo_actual,
            'concepto' => $concepto,
            'usuario_id' => auth('sanctum')->id(),
            'sucursal_id' => $caja->sucursal_id,
        ]);
    }
}