<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\ActivityLog;
use App\Models\Compra;
use App\Models\NotaCredito;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NotaCreditoService
{
    /**
     * Emitir una nota de crédito interna por la devolución de una venta o compra.
     *
     * @param  Venta|Compra  $documento
     */
    public function emitir(Model $documento, float $monto, ?string $motivo = null): NotaCredito
    {
        $esVenta = $documento instanceof Venta;

        $nota = NotaCredito::create([
            'numero' => NotaCredito::generateNumero(),
            'documento_tipo' => $esVenta ? 'Venta' : 'Compra',
            'documento_id' => $documento->id,
            'documento_numero' => $esVenta ? $documento->numero_venta : $documento->numero_compra,
            'monto' => $monto,
            'motivo' => $motivo,
            'estado' => NotaCredito::ESTADO_EMITIDA,
            'usuario_id' => auth('sanctum')->id(),
            'sucursal_id' => $documento->sucursal_id,
        ]);

        ActivityLog::registrar(
            'emitir_nota_credito',
            $esVenta ? 'Venta' : 'Compra',
            $documento->id,
            "{$nota->numero}: Bs {$monto} por devolución de {$nota->documento_numero}"
        );

        return $nota;
    }

    /**
     * Aplicar una nota de crédito emitida contra el saldo pendiente de un
     * documento de venta o compra. Reduce el saldo del documento y marca la
     * nota como aplicada.
     *
     * @param  Venta|Compra  $documento
     */
    public function aplicar(NotaCredito $nota, Model $documento): NotaCredito
    {
        if ($nota->estado !== NotaCredito::ESTADO_EMITIDA) {
            throw ApiException::conflict(
                'La nota de crédito no está emitida',
                ['estado' => ['Solo las notas emitidas pueden aplicarse']]
            );
        }

        if ((float) $documento->saldo_pendiente <= 0) {
            throw ApiException::conflict(
                'El documento no tiene saldo pendiente',
                ['saldo_pendiente' => ['No hay saldo al cual aplicar la nota']]
            );
        }

        $documentoEsVenta = $documento instanceof Venta;
        $notaEsVenta = $nota->documento_tipo === 'Venta';

        if ($documentoEsVenta !== $notaEsVenta) {
            throw ApiException::conflict(
                'La nota no corresponde al documento',
                ['documento_tipo' => ['La nota de crédito debe aplicarse a un '.($notaEsVenta ? 'Venta' : 'Compra')]]
            );
        }

        return DB::transaction(function () use ($nota, $documento) {
            $nota = NotaCredito::query()->lockForUpdate()->findOrFail($nota->id);
            $documento = $documento->newQuery()->lockForUpdate()->findOrFail($documento->id);

            $montoAplicar = min((float) $nota->monto, (float) $documento->saldo_pendiente);

            $documento->decrement('saldo_pendiente', $montoAplicar);
            $documento->increment('pagado', $montoAplicar);

            $numeroDocumento = $documento instanceof Venta
                ? $documento->numero_venta
                : $documento->numero_compra;

            $nota->update([
                'estado' => NotaCredito::ESTADO_APLICADA,
                'aplicado_a' => "{$nota->documento_tipo} {$numeroDocumento}",
            ]);

            ActivityLog::registrar(
                'aplicar_nota_credito',
                $nota->documento_tipo,
                $documento->id,
                "{$nota->numero}: aplicada Bs {$montoAplicar} sobre {$numeroDocumento}",
                null,
                ['monto' => $montoAplicar]
            );

            return $nota->fresh();
        }, 3);
    }

    /**
     * Anular una nota de crédito emitida.
     */
    public function anular(NotaCredito $nota, ?string $motivo = null): NotaCredito
    {
        if ($nota->estado === NotaCredito::ESTADO_ANULADA) {
            throw ApiException::conflict(
                'La nota de crédito ya está anulada',
                ['estado' => ['La nota de crédito ya fue anulada']]
            );
        }

        $nota->update([
            'estado' => NotaCredito::ESTADO_ANULADA,
            'aplicado_a' => $motivo ? trim(($nota->motivo ?? '')." | Anulada: {$motivo}") : $nota->motivo,
        ]);

        ActivityLog::registrar(
            'anular_nota_credito',
            'NotaCredito',
            $nota->id,
            "{$nota->numero} anulada"
        );

        return $nota->fresh();
    }
}