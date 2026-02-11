<?php

namespace App\Services;

use App\Models\Lote;
use App\Models\MovimientoLote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventarioService
{
    public function getLotesDisponibles(string $productoId, ?int $cantidadRequerida = null): array
    {
        $lotes = Lote::where('producto_id', $productoId)
            ->disponibles()
            ->fefo()
            ->get();

        $lotesFiltrados = $lotes->filter(function ($lote) use ($cantidadRequerida) {
            if ($cantidadRequerida === null) {
                return true;
            }
            return $lote->stock_disponible >= $cantidadRequerida;
        });

        return $lotesFiltrados->values()->toArray();
    }

    public function getLotesProximosAVencer(string $productoId, int $dias = 60): array
    {
        return Lote::where('producto_id', $productoId)
            ->proximosAVencer($dias)
            ->orderBy('fecha_vencimiento', 'asc')
            ->get()
            ->toArray();
    }

    public function getStockTotal(string $productoId): int
    {
        return Lote::where('producto_id', $productoId)
            ->where('estado', '!=', 'retirado')
            ->sum('stock');
    }

    public function getStockComprometido(string $productoId): int
    {
        return Lote::where('producto_id', $productoId)->sum('stock_comprometido');
    }

    public function getStockDisponible(string $productoId): int
    {
        return $this->getStockTotal($productoId) - $this->getStockComprometido($productoId);
    }

    public function crearLote(array $data): Lote
    {
        return DB::transaction(function () use ($data) {
            $lote = Lote::create($data);
            
            $this->registrarMovimiento([
                'lote_id' => $lote->id,
                'tipo_movimiento' => MovimientoLote::ENTRADA_COMPRA,
                'cantidad' => $lote->stock,
                'stock_anterior' => 0,
                'stock_nuevo' => $lote->stock,
                'documento_tipo' => 'InventarioInicial',
                'documento_id' => $lote->id,
                'observaciones' => 'Creación de lote por inventario inicial',
            ]);

            return $lote;
        });
    }

    public function agregarStock(string $loteId, int $cantidad, float $precioCosto, array $documento = []): array
    {
        return DB::transaction(function () use ($loteId, $cantidad, $precioCosto, $documento) {
            $lote = Lote::findOrFail($loteId);
            $stockAnterior = $lote->stock;
            
            $nuevoCostoTotal = ($stockAnterior * $lote->precio_costo_promedio) + ($cantidad * $precioCosto);
            $nuevoStock = $stockAnterior + $cantidad;
            $nuevoPrecioPromedio = $nuevoStock > 0 ? $nuevoCostoTotal / $nuevoStock : $precioCosto;

            $lote->update([
                'stock' => $nuevoStock,
                'precio_costo' => $precioCosto,
                'precio_costo_promedio' => $nuevoPrecioPromedio,
                'estado' => $nuevoStock <= $lote->stock_minimo ? 'parcial' : 'disponible',
            ]);

            $this->registrarMovimiento([
                'lote_id' => $loteId,
                'tipo_movimiento' => MovimientoLote::ENTRADA_COMPRA,
                'cantidad' => $cantidad,
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $nuevoStock,
                'documento_tipo' => $documento['tipo'] ?? 'Compra',
                'documento_id' => $documento['id'] ?? null,
                'documento_numero' => $documento['numero'] ?? null,
                'costo_unitario' => $precioCosto,
                'costo_total' => $cantidad * $precioCosto,
                'observaciones' => $documento['observaciones'] ?? null,
            ]);

            return [
                'lote' => $lote,
                'cantidad_agregada' => $cantidad,
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $nuevoStock,
            ];
        });
    }

    public function descontarStock(string $productoId, int $cantidad, array $documento = []): array
    {
        return DB::transaction(function () use ($productoId, $cantidad, $documento) {
            $lotes = $this->getLotesDisponibles($productoId);
            $lotesUtilizados = [];
            $restante = $cantidad;

            foreach ($lotes as $lote) {
                if ($restante <= 0) {
                    break;
                }

                $disponible = $lote->stock_disponible;
                $aDescontar = min($restante, $disponible);

                if ($aDescontar > 0) {
                    $stockAnterior = $lote->stock;
                    $lote->descontarStock($aDescontar);
                    $stockNuevo = $lote->stock;

                    $this->registrarMovimiento([
                        'lote_id' => $lote->id,
                        'tipo_movimiento' => MovimientoLote::SALIDA_VENTA,
                        'cantidad' => $aDescontar,
                        'stock_anterior' => $stockAnterior,
                        'stock_nuevo' => $stockNuevo,
                        'documento_tipo' => $documento['tipo'] ?? 'Venta',
                        'documento_id' => $documento['id'] ?? null,
                        'documento_numero' => $documento['numero'] ?? null,
                        'costo_unitario' => $lote->precio_costo_promedio,
                        'costo_total' => $aDescontar * $lote->precio_costo_promedio,
                        'observaciones' => $documento['observaciones'] ?? null,
                    ]);

                    $lotesUtilizados[] = [
                        'lote_id' => $lote->id,
                        'numero_lote' => $lote->numero_lote,
                        'cantidad' => $aDescontar,
                        'stock_restante' => $lote->stock,
                    ];

                    $restante -= $aDescontar;
                }
            }

            if ($restante > 0) {
                throw new \Exception("Stock insuficiente. Faltan {$restante} unidades.");
            }

            return [
                'producto_id' => $productoId,
                'cantidad_total' => $cantidad,
                'lotes_utilizados' => $lotesUtilizados,
                'documento' => $documento,
            ];
        });
    }

    public function descontarStockDeLote(string $loteId, int $cantidad, array $documento = []): array
    {
        return DB::transaction(function () use ($loteId, $cantidad, $documento) {
            $lote = Lote::findOrFail($loteId);
            
            if ($cantidad > $lote->stock_disponible) {
                throw new \Exception("Stock insuficiente en lote {$lote->numero_lote}");
            }

            $stockAnterior = $lote->stock;
            $lote->descontarStock($cantidad);
            $stockNuevo = $lote->stock;

            $this->registrarMovimiento([
                'lote_id' => $loteId,
                'tipo_movimiento' => MovimientoLote::SALIDA_VENTA,
                'cantidad' => $cantidad,
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $stockNuevo,
                'documento_tipo' => $documento['tipo'] ?? 'Venta',
                'documento_id' => $documento['id'] ?? null,
                'documento_numero' => $documento['numero'] ?? null,
                'costo_unitario' => $lote->precio_costo_promedio,
                'costo_total' => $cantidad * $lote->precio_costo_promedio,
                'observaciones' => $documento['observaciones'] ?? null,
            ]);

            return [
                'lote_id' => $loteId,
                'numero_lote' => $lote->numero_lote,
                'cantidad' => $cantidad,
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $stockNuevo,
            ];
        });
    }

    public function devolverStock(string $loteId, int $cantidad, ?float $precioCosto = null, array $documento = []): array
    {
        return DB::transaction(function () use ($loteId, $cantidad, $precioCosto, $documento) {
            $lote = Lote::findOrFail($loteId);
            $stockAnterior = $lote->stock;
            
            $lote->aumentarStock($cantidad, $precioCosto);
            $stockNuevo = $lote->stock;

            $this->registrarMovimiento([
                'lote_id' => $loteId,
                'tipo_movimiento' => MovimientoLote::ENTRADA_DEVOLUCION,
                'cantidad' => $cantidad,
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $stockNuevo,
                'documento_tipo' => $documento['tipo'] ?? 'Devolucion',
                'documento_id' => $documento['id'] ?? null,
                'documento_numero' => $documento['numero'] ?? null,
                'costo_unitario' => $precioCosto ?? $lote->precio_costo_promedio,
                'costo_total' => $cantidad * ($precioCosto ?? $lote->precio_costo_promedio),
                'observaciones' => $documento['observaciones'] ?? 'Devolución de cliente',
            ]);

            return [
                'lote_id' => $loteId,
                'numero_lote' => $lote->numero_lote,
                'cantidad' => $cantidad,
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $stockNuevo,
            ];
        });
    }

    public function marcarVencido(string $loteId): array
    {
        return DB::transaction(function () use ($loteId) {
            $lote = Lote::findOrFail($loteId);
            $stockAnterior = $lote->stock;
            
            $lote->update([
                'estado' => 'vencido',
                'notas' => ($lote->notas ? $lote->notas . "\n" : '') . now() . ": Marcado como vencido",
            ]);

            $this->registrarMovimiento([
                'lote_id' => $loteId,
                'tipo_movimiento' => MovimientoLote::SALIDA_MERMA,
                'cantidad' => 0,
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $stockAnterior,
                'observaciones' => 'Lote marcado como vencido',
            ]);

            return [
                'lote_id' => $loteId,
                'numero_lote' => $lote->numero_lote,
                'stock' => $stockAnterior,
                'estado' => 'vencido',
            ];
        });
    }

    public function registrarMovimiento(array $data): MovimientoLote
    {
        $userId = auth()->id();
        $sucursalId = auth()->user()?->sucursal_id;

        $movimiento = MovimientoLote::create(array_merge($data, [
            'usuario_id' => $data['usuario_id'] ?? $userId,
            'sucursal_id' => $data['sucursal_id'] ?? $sucursalId,
        ]));

        Log::info('Movimiento de lote registrado', [
            'tipo' => $movimiento->tipo_movimiento,
            'lote_id' => $movimiento->lote_id,
            'cantidad' => $movimiento->cantidad,
            'documento' => $movimiento->documento_tipo . ' - ' . ($movimiento->documento_id ?? 'N/A'),
        ]);

        return $movimiento;
    }

    public function getKardex(string $loteId, ?string $fechaInicio = null, ?string $fechaFin = null): array
    {
        $query = MovimientoLote::where('lote_id', $loteId)->recientes();

        if ($fechaInicio && $fechaFin) {
            $query->entreFechas($fechaInicio, $fechaFin);
        }

        return $query->get()->toArray();
    }

    public function getKardexPorProducto(string $productoId, ?string $fechaInicio = null, ?string $fechaFin = null): array
    {
        $lotesIds = Lote::where('producto_id', $productoId)->pluck('id');
        
        $query = MovimientoLote::whereIn('lote_id', $lotesIds)->recientes();

        if ($fechaInicio && $fechaFin) {
            $query->entreFechas($fechaInicio, $fechaFin);
        }

        return $query->get()->toArray();
    }

    public function getResumenInventario(): array
    {
        return [
            'total_productos' => \App\Models\Producto::count(),
            'total_lotes' => Lote::count(),
            'lotes_activos' => Lote::whereIn('estado', ['disponible', 'parcial'])->count(),
            'lotes_vencidos' => Lote::where('estado', 'vencido')->count(),
            'lotes_proximos_30_dias' => Lote::proximosAVencer(30)->count(),
            'stock_bajo' => Lote::stockBajo()->count(),
            'valor_inventario' => Lote::where('estado', '!=', 'retirado')
                ->get()
                ->sum(fn($l) => $l->stock * $l->precio_costo_promedio),
        ];
    }

    public function getProductosStockBajo(): array
    {
        $lotes = Lote::stockBajo()
            ->with('producto')
            ->get()
            ->groupBy('producto_id');

        $resultado = [];
        foreach ($lotes as $productoId => $lotesProducto) {
            $producto = $lotesProducto->first()->producto;
            $stockTotal = $lotesProducto->sum('stock');
            $stockMinimo = $lotesProducto->min('stock_minimo');
            
            $resultado[] = [
                'producto_id' => $productoId,
                'producto_nombre' => $producto->nombre,
                'codigo_barras' => $producto->codigo_barras,
                'stock_total' => $stockTotal,
                'stock_minimo' => $stockMinimo,
                'lotes_count' => $lotesProducto->count(),
                'lotes' => $lotesProducto->map(fn($l) => [
                    'lote_id' => $l->id,
                    'numero_lote' => $l->numero_lote,
                    'stock' => $l->stock,
                    'fecha_vencimiento' => $l->fecha_vencimiento,
                ])->toArray(),
            ];
        }

        return $resultado;
    }

    public function getProductosProximosAVencer(int $dias = 60): array
    {
        $lotes = Lote::proximosAVencer($dias)
            ->with('producto')
            ->orderBy('fecha_vencimiento', 'asc')
            ->get();

        return $lotes->map(function ($lote) {
            return [
                'lote_id' => $lote->id,
                'producto_id' => $lote->producto_id,
                'producto_nombre' => $lote->producto->nombre,
                'codigo_barras' => $lote->producto->codigo_barras,
                'numero_lote' => $lote->numero_lote,
                'stock' => $lote->stock,
                'fecha_vencimiento' => $lote->fecha_vencimiento,
                'dias_restantes' => $lote->dias_para_vencer,
            ];
        })->toArray();
    }

    public function transferirEntreLotes(string $loteOrigenId, string $loteDestinoId, int $cantidad): array
    {
        return DB::transaction(function () use ($loteOrigenId, $loteDestinoId, $cantidad) {
            $loteOrigen = Lote::findOrFail($loteOrigenId);
            $loteDestino = Lote::findOrFail($loteDestinoId);

            if ($cantidad > $loteOrigen->stock_disponible) {
                throw new \Exception("Stock insuficiente en lote origen");
            }

            $stockAnteriorOrigen = $loteOrigen->stock;
            $loteOrigen->descontarStock($cantidad);
            
            $stockAnteriorDestino = $loteDestino->stock;
            $loteDestino->aumentarStock($cantidad, $loteOrigen->precio_costo_promedio);

            $this->registrarMovimiento([
                'lote_id' => $loteOrigenId,
                'tipo_movimiento' => MovimientoLote::AJUSTE_NEGATIVO,
                'cantidad' => $cantidad,
                'stock_anterior' => $stockAnteriorOrigen,
                'stock_nuevo' => $loteOrigen->stock,
                'observaciones' => "Transferencia a lote {$loteDestino->numero_lote}",
            ]);

            $this->registrarMovimiento([
                'lote_id' => $loteDestinoId,
                'tipo_movimiento' => MovimientoLote::AJUSTE_POSITIVO,
                'cantidad' => $cantidad,
                'stock_anterior' => $stockAnteriorDestino,
                'stock_nuevo' => $loteDestino->stock,
                'observaciones' => "Transferencia desde lote {$loteOrigen->numero_lote}",
            ]);

            return [
                'lote_origen' => [
                    'id' => $loteOrigenId,
                    'numero_lote' => $loteOrigen->numero_lote,
                    'stock_anterior' => $stockAnteriorOrigen,
                    'stock_nuevo' => $loteOrigen->stock,
                ],
                'lote_destino' => [
                    'id' => $loteDestinoId,
                    'numero_lote' => $loteDestino->numero_lote,
                    'stock_anterior' => $stockAnteriorDestino,
                    'stock_nuevo' => $loteDestino->stock,
                ],
                'cantidad_transferida' => $cantidad,
            ];
        });
    }
}
