<?php

namespace App\Models;

use App\Exceptions\ApiException;
use App\Models\Concerns\ScopedBySucursal;
use App\Services\CajaLibroService;
use App\Services\NotaCreditoService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Map de columnas para ventas
 *
 * @property-read string $id
 * @property-read string $numero_venta
 * @property-read string $subtotal
 * @property-read string $descuento
 * @property-read string $impuestos
 * @property-read string $total
 * @property-read string $estado
 * @property-read string $metodo_pago
 * @property-read ?string $cliente_id
 * @property-read ?string $usuario_id
 * @property-read ?string $sucursal_id
 * @property-read ?string $caja_id
 * @property-read ?string $notas
 * @property-read \Illuminate\Support\Carbon $fecha_venta
 * @property-read ?\Illuminate\Support\Carbon $created_at
 * @property-read ?\Illuminate\Support\Carbon $updated_at
 * @property-read string $pagado
 * @property-read string $saldo_pendiente
 * @property-read string $tipo_pago
 * @property-read ?string $observaciones
 * @property-read ?string $motivo_cancelacion
 * @property-read ?\Illuminate\Support\Carbon $fecha_cancelacion
 * @property-read ?\Illuminate\Support\Carbon $fecha_vencimiento
 */
class Venta extends Model
{
    use ScopedBySucursal;
    use HasFactory, HasUuids;

    protected $table = 'ventas';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'numero_venta',
        'subtotal',
        'descuento',
        'impuestos',
        'total',
        'pagado',
        'saldo_pendiente',
        'estado',
        'metodo_pago',
        'tipo_pago',
        'observaciones',
        'motivo_cancelacion',
        'fecha_cancelacion',
        'cliente_id',
        'usuario_id',
        'sucursal_id',
        'caja_id',
        'notas',
        'fecha_venta',
        'fecha_vencimiento',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'impuestos' => 'decimal:2',
        'total' => 'decimal:2',
        'pagado' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
        'fecha_venta' => 'datetime',
        'fecha_vencimiento' => 'date',
        'fecha_cancelacion' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con Cliente
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Relación con Usuario (vendedor)
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Relación con Sucursal
     */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    /**
     * Relación con Caja
     */
    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'caja_id');
    }

    /**
     * Relación con productos de la venta
     */
    public function productos(): HasMany
    {
        return $this->hasMany(VentaProducto::class, 'venta_id');
    }

    /**
     * Relación con productos de la venta (alias para ventaProductos)
     */
    public function ventaProductos(): HasMany
    {
        return $this->hasMany(VentaProducto::class, 'venta_id');
    }

    /**
     * Relación con los pagos asociados a la venta.
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'documento_id')
            ->where('documento_tipo', 'Venta');
    }

    /**
     * Relación con las notas de crédito emitidas por devoluciones.
     */
    public function notasCredito(): HasMany
    {
        return $this->hasMany(NotaCredito::class, 'documento_id')
            ->where('documento_tipo', 'Venta');
    }

    /**
     * Salidas de inventario (lotes/kardex) imputadas a esta venta.
     */
    public function salidasInventario(): HasMany
    {
        return $this->hasMany(MovimientoLote::class, 'documento_id')
            ->where('documento_tipo', 'Venta')
            ->where('tipo_movimiento', MovimientoLote::SALIDA_VENTA);
    }

    /**
     * Generar número de venta automático
     */
    public static function generateNumeroVenta(): string
    {
        $lastVenta = self::orderBy('created_at', 'desc')->first();
        $lastNumber = $lastVenta ? (int) substr($lastVenta->numero_venta, 2) : 0;
        return 'V-' . str_pad($lastNumber + 1, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Calcular totales de la venta
     */
    public function calcularTotales(): void
    {
        $subtotal = $this->productos()->sum('subtotal');
        $this->subtotal = $subtotal;
        $this->total = $subtotal - $this->descuento + $this->impuestos;
        $this->save();
    }

    /**
     * Verificar si la venta puede ser cancelada
     */
    public function puedeSerCancelada(): bool
    {
        return in_array($this->estado, ['pendiente', 'completada'], true);
    }

    /**
     * Cancelar venta. Si ya estaba completada, revierte inventario y caja.
     *
     * @throws ApiException
     */
    public function cancelar(?string $motivo = null): void
    {
        if (! $this->puedeSerCancelada()) {
            throw ApiException::conflict(
                'La venta no puede ser cancelada',
                ['estado' => ["La venta está en estado: {$this->estado}"]]
            );
        }

        DB::transaction(function () use ($motivo) {
            $this->revertirInventario();

            $this->update([
                'estado' => 'cancelada',
                'motivo_cancelacion' => $motivo,
                'fecha_cancelacion' => now(),
            ]);
        });
    }

    /**
     * Completar venta
     */
    public function completar(): void
    {
        if ($this->estado === 'pendiente') {
            $this->estado = 'completada';
            $this->save();
        }
    }

    /**
     * Devolver una venta completada, revirtiendo inventario, caja y emitiendo
     * la nota de crédito interna correspondiente.
     *
     * @throws ApiException
     */
    public function devolver(?string $motivo = null): void
    {
        if ($this->estado !== 'completada') {
            throw ApiException::conflict(
                'Solo se pueden devolver ventas completadas',
                ['estado' => ["La venta está en estado: {$this->estado}"]]
            );
        }

        DB::transaction(function () use ($motivo) {
            $this->revertirInventario(MovimientoCaja::ORIGEN_NOTA_CREDITO);

            $updateData = ['estado' => 'devuelta'];

            if ($motivo) {
                $updateData['motivo_cancelacion'] = $motivo;
            }

            $this->update($updateData);

            if ((float) $this->pagado > 0) {
                app(NotaCreditoService::class)->emitir($this, (float) $this->pagado, $motivo);
            }
        });
    }

    /**
     * Revertir inventario, lotes y caja de una venta completada.
     */
    public function revertirInventario(string $origen = MovimientoCaja::ORIGEN_VENTA): void
    {
        if ($this->estado !== 'completada') {
            return;
        }

        DB::transaction(function () use ($origen) {
            $usuario = auth()->user();
            $venta = Venta::query()->lockForUpdate()->findOrFail($this->id);

            // Restaurar stock a nivel producto (compatible con ventas sin lotes)
            foreach ($venta->ventaProductos()->get() as $item) {
                if ($item->producto) {
                    $item->producto->increment('stock_actual', $item->cantidad);
                }
            }

            // Restaurar lotes desde el libro de movimientos (SALIDA_VENTA)
            $movimientos = MovimientoLote::query()
                ->where('documento_tipo', 'Venta')
                ->where('documento_id', $venta->id)
                ->where('tipo_movimiento', MovimientoLote::SALIDA_VENTA)
                ->get();

            foreach ($movimientos as $movimiento) {
                if (! $movimiento->lote_id) {
                    continue;
                }

                $lote = Lote::query()->lockForUpdate()->find($movimiento->lote_id);

                if (! $lote) {
                    continue;
                }

                $stockAnterior = $lote->stock;
                $lote->stock += $movimiento->cantidad;

                if ($lote->stock > 0 && $lote->estado === 'agotado') {
                    $lote->estado = 'disponible';
                }

                $lote->save();

                MovimientoLote::create([
                    'lote_id' => $movimiento->lote_id,
                    'tipo_movimiento' => MovimientoLote::ENTRADA_DEVOLUCION,
                    'cantidad' => $movimiento->cantidad,
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => $lote->stock,
                    'documento_tipo' => 'Venta',
                    'documento_id' => $venta->id,
                    'documento_numero' => $venta->numero_venta,
                    'usuario_id' => $usuario?->id,
                    'sucursal_id' => $venta->sucursal_id,
                    'producto_nombre' => $lote->producto?->nombre,
                    'producto_codigo' => $lote->producto?->codigo_barras,
                    'costo_unitario' => $movimiento->costo_unitario,
                    'costo_total' => $movimiento->costo_total,
                    'observaciones' => "Reversión de salida por venta {$venta->numero_venta}",
                ]);
            }

            if ((float) $venta->pagado > 0 && $venta->caja_id) {
                app(CajaLibroService::class)->egreso(
                    $venta->caja_id,
                    (float) $venta->pagado,
                    $origen,
                    'Venta',
                    $venta->id,
                    $venta->numero_venta,
                    "Reversión de caja por devolución de venta {$venta->numero_venta}"
                );
            }
        });
    }
}
