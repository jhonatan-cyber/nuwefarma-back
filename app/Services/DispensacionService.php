<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApiException;
use App\Enums\CondicionVentaEnum;
use App\Models\Lote;
use App\Models\MovimientoLote;
use App\Models\Receta;
use App\Models\RecetaProducto;
use Illuminate\Support\Facades\DB;

class DispensacionService
{
    /**
     * Dispensar productos de una receta de forma total o parcial.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    public function dispensar(Receta $receta, array $items, array $opts = []): Receta
    {
        if (in_array($receta->estado, [Receta::ESTADO_ATENDIDA, Receta::ESTADO_ANULADA], true)) {
            throw ApiException::conflict(
                "La receta está {$receta->estado} y no admite dispensaciones",
                ['estado' => [$receta->estado]]
            );
        }

        if ($receta->fecha_vencimiento && $receta->fecha_vencimiento->lt(now()->toDateString())) {
            throw ApiException::conflict(
                'La receta está vencida',
                ['fecha_vencimiento' => ['La receta venció el '.$receta->fecha_vencimiento->format('d/m/Y')]]
            );
        }

        return DB::transaction(function () use ($receta, $items, $opts) {
            $receta = Receta::query()->lockForUpdate()->findOrFail($receta->id);

            foreach ($items as $item) {
                $this->dispensarItem($receta, $item, $opts);
            }

            $receta->recalcularEstado();

            return $receta->fresh()->load(['medico', 'paciente', 'productos.producto']);
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<string, mixed>  $opts
     */
    private function dispensarItem(Receta $receta, array $item, array $opts): void
    {
        $recetaProducto = RecetaProducto::query()->lockForUpdate()->find($item['receta_producto_id'] ?? null);

        if (! $recetaProducto || $recetaProducto->receta_id !== $receta->id) {
            throw ApiException::validation([
                'items' => ['Uno de los productos no pertenece a la receta'],
            ]);
        }

        if (in_array($recetaProducto->estado, [RecetaProducto::ESTADO_DISPENSADO, RecetaProducto::ESTADO_ANULADO], true)) {
            throw ApiException::conflict(
                'El producto ya fue dispensado o anulado',
                ['items' => ["El producto {$recetaProducto->producto->nombre} no admite más dispensación"]]
            );
        }

        $cantidad = (int) ($item['cantidad'] ?? 0);
        $pendiente = $recetaProducto->getPendiente();

        if ($cantidad <= 0) {
            throw ApiException::validation([
                'items' => ['La cantidad debe ser mayor a cero'],
            ]);
        }

        if ($cantidad > $pendiente) {
            throw ApiException::conflict(
                'La cantidad excede lo prescrito',
                ['items' => ["Solo quedan {$pendiente} por dispensar de {$recetaProducto->producto->nombre}"]]
            );
        }

        $producto = $recetaProducto->producto;

        // Regla de medicamentos controlados: receta retenida exige autorización.
        if ($producto->condicion_venta === CondicionVentaEnum::RECETA_RETENIDA
            && empty($opts['autorizacion_controlado'])) {
            throw ApiException::conflict(
                'Este medicamento es controlado y requiere autorización',
                ['autorizacion_controlado' => ['Debe indicar la autorización para dispensar el medicamento controlado']]
            );
        }

        $lote = $this->resolverLote($producto->id, $item['lote_id'] ?? null, $cantidad);

        if (! $lote) {
            throw ApiException::conflict(
                'No hay stock disponible del producto',
                ['cantidad' => ["Sin stock de: {$producto->nombre}"]]
            );
        }

        $stockAnterior = $lote->stock;

        $lote->descontarStock($cantidad);

        MovimientoLote::create([
            'lote_id' => $lote->id,
            'tipo_movimiento' => MovimientoLote::SALIDA_DISPENSACION,
            'cantidad' => $cantidad,
            'stock_anterior' => $stockAnterior,
            'stock_nuevo' => $lote->fresh()->stock,
            'documento_tipo' => 'Receta',
            'documento_id' => $receta->id,
            'documento_numero' => $receta->numero_receta,
            'usuario_id' => $opts['usuario_id'] ?? auth('sanctum')->id(),
            'sucursal_id' => $receta->sucursal_id,
            'producto_nombre' => $producto->nombre,
            'producto_codigo' => $producto->codigo_barras,
            'costo_unitario' => $lote->precio_costo_promedio ?? $lote->precio_costo,
            'costo_total' => round($cantidad * (float) ($lote->precio_costo_promedio ?? $lote->precio_costo), 2),
            'observaciones' => "Dispensación de receta {$receta->numero_receta}",
        ]);

        $nuevaDispensada = (float) $recetaProducto->cantidad_dispensada + $cantidad;
        $recetaProducto->update([
            'cantidad_dispensada' => round($nuevaDispensada, 2),
            'estado' => $nuevaDispensada >= (float) $recetaProducto->cantidad_prescrita
                ? RecetaProducto::ESTADO_DISPENSADO
                : RecetaProducto::ESTADO_PARCIAL,
        ]);

        // Traza regulatoria para medicamentos controlados.
        if ($producto->condicion_venta === CondicionVentaEnum::RECETA_RETENIDA) {
            \App\Models\LibroControlado::create([
                'producto_id' => $producto->id,
                'lote_id' => $lote->id,
                'receta_id' => $receta->id,
                'receta_numero' => $receta->numero_receta,
                'paciente_id' => $receta->paciente_id,
                'medico_id' => $receta->medico_id,
                'cantidad' => $cantidad,
                'autorizacion' => $opts['autorizacion_controlado'] ?? null,
                'cajero_id' => $opts['usuario_id'] ?? auth('sanctum')->id(),
                'sucursal_id' => $receta->sucursal_id,
                'observaciones' => $item['observaciones'] ?? null,
            ]);
        }
    }

    /**
     * Resolver el lote a dispensar: el indicado por el usuario o el próximo
     * por vencer (FEFO) con stock suficiente.
     */
    private function resolverLote(string $productoId, ?string $loteId, int $cantidad): ?Lote
    {
        $query = Lote::query()
            ->porProducto($productoId)
            ->disponibles()
            ->where('stock', '>=', $cantidad);

        if ($loteId) {
            $lote = $query->firstWhere('id', $loteId);

            return $lote ?: null;
        }

        return $query->fefo()->first();
    }
}