<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\ConteoInventario;
use App\Models\ConteoInventarioItem;
use App\Models\Lote;
use App\Models\MovimientoLote;
use Illuminate\Support\Facades\DB;

class ConteoInventarioService
{
    /**
     * Cerrar un conteo aplicando las diferencias a los lotes.
     * Cada diferencia genera un movimiento de kardex de ajuste
     * (positivo o negativo) con trazabilidad completa.
     *
     * @return array{conteo: ConteoInventario, ajustes: array<int, array<string, mixed>>}
     */
    public function cerrar(ConteoInventario $conteo, string $usuarioId): array
    {
        if ($conteo->estado === ConteoInventario::ESTADO_CERRADO) {
            throw ApiException::conflict('El conteo ya fue cerrado');
        }

        if ($conteo->estado === ConteoInventario::ESTADO_CANCELADO) {
            throw ApiException::conflict('El conteo fue cancelado y no puede cerrarse');
        }

        $ajustes = [];

        $conteo = DB::transaction(function () use ($conteo, $usuarioId, &$ajustes) {
            $conteo = ConteoInventario::query()->lockForUpdate()->findOrFail($conteo->id);

            $items = $conteo->items()->lockForUpdate()->with(['lote', 'producto'])->get();

            foreach ($items as $item) {
                if ($item->estado !== ConteoInventarioItem::ESTADO_CONTADO) {
                    throw ApiException::conflict(
                        'Todos los ítems deben estar conteados antes de cerrar',
                        ['items' => ["Item {$item->id} sin contar"]]
                    );
                }

                $diferencia = (int) $item->diferencia;
                if ($diferencia === 0 || ! $item->lote) {
                    continue;
                }

                $lote = $item->lote;
                $stockAnterior = $lote->stock;

                if ($diferencia > 0) {
                    $lote->aumentarStock($diferencia);
                    $tipo = MovimientoLote::AJUSTE_POSITIVO;
                } else {
                    $stockDisponible = $lote->stock - $lote->stock_comprometido;
                    if (abs($diferencia) > $stockDisponible) {
                        throw ApiException::conflict(
                            'La diferencia excede el stock disponible del lote',
                            ['items' => ["Lote {$lote->numero_lote} no tiene stock suficiente para la diferencia calculada"]]
                        );
                    }
                    $lote->descontarStock(abs($diferencia));
                    $tipo = MovimientoLote::AJUSTE_NEGATIVO;
                }

                $stockNuevo = $lote->fresh()->stock;

                MovimientoLote::create([
                    'lote_id' => $lote->id,
                    'tipo_movimiento' => $tipo,
                    'cantidad' => abs($diferencia),
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => $stockNuevo,
                    'documento_tipo' => 'ConteoInventario',
                    'documento_id' => $conteo->id,
                    'documento_numero' => $conteo->numero_conteo,
                    'usuario_id' => $usuarioId,
                    'sucursal_id' => $conteo->sucursal_id,
                    'producto_nombre' => $item->producto?->nombre,
                    'producto_codigo' => $item->producto?->codigo_barras,
                    'costo_unitario' => $lote->precio_costo_promedio ?? $lote->precio_costo,
                    'costo_total' => round(abs($diferencia) * (float) ($lote->precio_costo_promedio ?? $lote->precio_costo), 2),
                    'observaciones' => "Ajuste por conteo {$conteo->numero_conteo}",
                ]);

                $ajustes[] = [
                    'item_id' => $item->id,
                    'producto' => $item->producto?->nombre,
                    'lote' => $lote->numero_lote,
                    'diferencia' => $diferencia,
                    'tipo' => $tipo,
                ];
            }

            $conteo->update([
                'estado' => ConteoInventario::ESTADO_CERRADO,
                'fecha_cierre' => now(),
                'cerrado_por_id' => $usuarioId,
            ]);

            return $conteo;
        }, 3);

        return ['conteo' => $conteo->load(['items.producto', 'sucursal', 'responsable']), 'ajustes' => $ajustes];
    }
}