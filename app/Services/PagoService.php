<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\ActivityLog;
use App\Models\Caja;
use App\Models\Compra;
use App\Models\MovimientoCaja;
use App\Models\Pago;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Model;

class PagoService
{
    public function __construct(private CajaLibroService $cajaLibro) {}

    /**
     * Registrar un pago sobre una venta o compra: crea el comprobante de
     * pago, actualiza la caja y deja la traza del movimiento.
     *
     * El documento (venta/compra) ya debe tener sus campos pagado/saldo
     * actualizados por el llamador; este servicio solo registra el evento.
     *
     * @param  Venta|Compra  $documento
     * @param  array<string, mixed>  $opts
     */
    public function registrar(Model $documento, float $monto, array $opts = []): Pago
    {
        if ($monto <= 0) {
            throw ApiException::conflict(
                'El monto del pago debe ser mayor a cero',
                ['monto' => ['No se puede registrar un pago de Bs 0']]
            );
        }

        if ($documento->caja_id) {
            $caja = Caja::query()->find($documento->caja_id);

            if ($caja && $caja->estado === 'cerrada') {
                throw ApiException::conflict(
                    'La caja está cerrada',
                    ['caja_id' => ['No se pueden registrar pagos en una caja cerrada']]
                );
            }
        }

        $esVenta = $documento instanceof Venta;
        $esIngreso = $esVenta;

        $tipoPago = $opts['tipo_pago']
            ?? ($monto >= (float) $documento->saldo_pendiente ? 'total' : 'parcial');

        $numero = $esVenta ? $documento->numero_venta : $documento->numero_compra;

        $pago = Pago::create([
            'numero_pago' => Pago::generateNumeroPago(),
            'documento_tipo' => $esVenta ? 'Venta' : 'Compra',
            'documento_id' => $documento->id,
            'documento_numero' => $numero,
            'fecha_pago' => now(),
            'monto' => $monto,
            'metodo_pago' => $opts['metodo_pago'] ?? $documento->metodo_pago ?? 'efectivo',
            'tipo_pago' => $tipoPago,
            'referencia' => $opts['referencia'] ?? null,
            'nota' => $opts['nota'] ?? null,
            'caja_id' => $documento->caja_id ?? null,
            'usuario_id' => $opts['usuario_id'] ?? auth('sanctum')->id(),
            'sucursal_id' => $opts['sucursal_id'] ?? $documento->sucursal_id,
        ]);

        $concepto = $esVenta
            ? "Pago {$pago->numero_pago} correspondiente a la venta {$numero}"
            : "Pago {$pago->numero_pago} correspondiente a la compra {$numero}";

        if ($pago->caja_id) {
            if ($esIngreso) {
                $origen = $tipoPago === 'abono' ? MovimientoCaja::ORIGEN_ABONO : MovimientoCaja::ORIGEN_VENTA;
                $this->cajaLibro->ingreso($pago->caja_id, $monto, $origen, $pago->documento_tipo, $pago->documento_id, $pago->documento_numero, $concepto);
            } else {
                $this->cajaLibro->egreso($pago->caja_id, $monto, MovimientoCaja::ORIGEN_COMPRA, $pago->documento_tipo, $pago->documento_id, $pago->documento_numero, $concepto);
            }
        }

        $accion = $esVenta
            ? ($tipoPago === 'abono' ? 'abonar_venta' : 'registrar_pago_venta')
            : 'registrar_pago_compra';

        ActivityLog::registrar(
            $accion,
            $esVenta ? 'Venta' : 'Compra',
            $documento->id,
            "{$pago->numero_pago}: Bs {$monto} sobre {$numero}",
            null,
            [
                'monto' => $monto,
                'tipo_pago' => $tipoPago,
                'metodo_pago' => $pago->metodo_pago,
            ]
        );

        return $pago;
    }
}