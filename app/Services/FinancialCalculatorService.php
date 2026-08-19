<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MovimientoLote;
use App\Models\Venta;

class FinancialCalculatorService
{
    /**
     * Costo real de una venta según las salidas de inventario (lotes/kardex).
     */
    public function costoVenta(Venta $venta): float
    {
        return (float) MovimientoLote::query()
            ->where('documento_tipo', 'Venta')
            ->where('documento_id', $venta->id)
            ->where('tipo_movimiento', MovimientoLote::SALIDA_VENTA)
            ->sum('costo_total');
    }

    /**
     * Utilidad bruta de una venta: ingreso neto por venta menos su costo.
     */
    public function utilidadVenta(Venta $venta): float
    {
        return round((float) $venta->total - $this->costoVenta($venta), 2);
    }

    /**
     * Margen de utilidad porcentual de una venta.
     */
    public function margenVenta(Venta $venta): float
    {
        $total = (float) $venta->total;

        return $total > 0 ? round(($this->utilidadVenta($venta) / $total) * 100, 2) : 0.0;
    }

    /**
     * Total efectivamente cobrado de una venta.
     */
    public function totalCobrado(Venta $venta): float
    {
        return (float) $venta->pagado;
    }

    /**
     * Saldo por cobrar de una venta.
     */
    public function saldoPorCobrar(Venta $venta): float
    {
        return max(0, (float) $venta->saldo_pendiente);
    }
}