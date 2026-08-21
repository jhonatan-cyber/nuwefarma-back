<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Map de columnas para movimientos_lote
 *
 * @property-read string $id
 * @property-read string $lote_id
 * @property-read string $tipo_movimiento
 * @property-read int $cantidad
 * @property-read int $stock_anterior
 * @property-read int $stock_nuevo
 * @property-read ?string $documento_tipo
 * @property-read ?string $documento_id
 * @property-read ?string $documento_numero
 * @property-read ?string $usuario_id
 * @property-read ?string $sucursal_id
 * @property-read ?string $producto_nombre
 * @property-read ?string $producto_codigo
 * @property-read ?string $costo_unitario
 * @property-read ?string $costo_total
 * @property-read ?string $observaciones
 * @property-read ?\Illuminate\Support\Carbon $created_at
 * @property-read ?\Illuminate\Support\Carbon $updated_at
 */
class MovimientoLote extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'movimientos_lote';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'lote_id',
        'tipo_movimiento',
        'cantidad',
        'stock_anterior',
        'stock_nuevo',
        'documento_tipo',
        'documento_id',
        'documento_numero',
        'usuario_id',
        'sucursal_id',
        'producto_nombre',
        'producto_codigo',
        'costo_unitario',
        'costo_total',
        'observaciones',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'stock_anterior' => 'integer',
        'stock_nuevo' => 'integer',
        'costo_unitario' => 'decimal:2',
        'costo_total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Constantes para tipos de movimiento
     */
    public const ENTRADA_COMPRA = 'ENTRADA_COMPRA';

    public const ENTRADA_DEVOLUCION = 'ENTRADA_DEVOLUCION';

    public const ENTRADA_TRASLADO_IN = 'ENTRADA_TRASLADO_IN';

    public const SALIDA_VENTA = 'SALIDA_VENTA';

    public const SALIDA_DISPENSACION = 'SALIDA_DISPENSACION';

    public const SALIDA_DEVOLUCION_PROV = 'SALIDA_DEVOLUCION_PROV';

    public const SALIDA_TRASLADO_OUT = 'SALIDA_TRASLADO_OUT';

    public const AJUSTE_POSITIVO = 'AJUSTE_POSITIVO';

    public const AJUSTE_NEGATIVO = 'AJUSTE_NEGATIVO';

    public const SALIDA_MERMA = 'SALIDA_MERMA';

    public const SALIDA_RETIRADO = 'SALIDA_RETIRADO';

    /**
     * Relación con Lote
     */
    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'lote_id');
    }

    /**
     * Relación con Usuario
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
     * Scope para movimientos de entrada
     */
    public function scopeEntradas(Builder $query): Builder
    {
        return $query->whereIn('tipo_movimiento', [
            self::ENTRADA_COMPRA,
            self::ENTRADA_DEVOLUCION,
            self::ENTRADA_TRASLADO_IN,
            self::AJUSTE_POSITIVO,
        ]);
    }

    /**
     * Scope para movimientos de salida
     */
    public function scopeSalidas(Builder $query): Builder
    {
        return $query->whereIn('tipo_movimiento', [
            self::SALIDA_VENTA,
            self::SALIDA_DISPENSACION,
            self::SALIDA_DEVOLUCION_PROV,
            self::SALIDA_TRASLADO_OUT,
            self::AJUSTE_NEGATIVO,
            self::SALIDA_MERMA,
            self::SALIDA_RETIRADO,
        ]);
    }

    /**
     * Scope para movimientos por tipo
     */
    public function scopePorTipo(Builder $query, string $tipo): Builder
    {
        return $query->where('tipo_movimiento', $tipo);
    }

    /**
     * Scope para movimientos por documento
     */
    public function scopePorDocumento(Builder $query, string $documentoId): Builder
    {
        return $query->where('documento_id', $documentoId);
    }

    /**
     * Scope para movimientos por lote
     */
    public function scopePorLote(Builder $query, string $loteId): Builder
    {
        return $query->where('lote_id', $loteId);
    }

    /**
     * Scope para movimientos por usuario
     */
    public function scopePorUsuario(Builder $query, string $usuarioId): Builder
    {
        return $query->where('usuario_id', $usuarioId);
    }

    /**
     * Scope para movimientos por sucursal
     */
    public function scopePorSucursal(Builder $query, string $sucursalId): Builder
    {
        return $query->where('sucursal_id', $sucursalId);
    }

    /**
     * Scope para movimientos en un rango de fechas
     */
    public function scopeEntreFechas(Builder $query, string $fechaInicio, string $fechaFin): Builder
    {
        return $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
    }

    /**
     * Scope para ordenar por fecha (más recientes primero)
     */
    public function scopeRecientes(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Verificar si es una entrada
     */
    public function esEntrada(): bool
    {
        return in_array($this->tipo_movimiento, [
            self::ENTRADA_COMPRA,
            self::ENTRADA_DEVOLUCION,
            self::ENTRADA_TRASLADO_IN,
            self::AJUSTE_POSITIVO,
        ]);
    }

    /**
     * Verificar si es una salida
     */
    public function esSalida(): bool
    {
        return in_array($this->tipo_movimiento, [
            self::SALIDA_VENTA,
            self::SALIDA_DISPENSACION,
            self::SALIDA_DEVOLUCION_PROV,
            self::SALIDA_TRASLADO_OUT,
            self::AJUSTE_NEGATIVO,
            self::SALIDA_MERMA,
            self::SALIDA_RETIRADO,
        ]);
    }

    /**
     * Obtener el tipo de movimiento formateado
     */
    public function getTipoFormateadoAttribute(): string
    {
        $tipos = [
            self::ENTRADA_COMPRA => 'Entrada por Compra',
            self::ENTRADA_DEVOLUCION => 'Entrada por Devolución',
            self::ENTRADA_TRASLADO_IN => 'Entrada por Traslado',
            self::SALIDA_VENTA => 'Salida por Venta',
            self::SALIDA_DISPENSACION => 'Dispensación a Paciente',
            self::SALIDA_DEVOLUCION_PROV => 'Salida por Devolución a Proveedor',
            self::SALIDA_TRASLADO_OUT => 'Salida por Traslado',
            self::AJUSTE_POSITIVO => 'Ajuste Positivo',
            self::AJUSTE_NEGATIVO => 'Ajuste Negativo',
            self::SALIDA_MERMA => 'Salida por Merma',
            self::SALIDA_RETIRADO => 'Salida por Retiro',
        ];

        return $tipos[$this->tipo_movimiento] ?? $this->tipo_movimiento;
    }

    /**
     * Obtener clase CSS según el tipo de movimiento
     */
    public function getTipoClassAttribute(): string
    {
        if ($this->esEntrada()) {
            return 'text-green-600 bg-green-100';
        }

        return 'text-red-600 bg-red-100';
    }

    /**
     * Obtener icono según el tipo de movimiento
     */
    public function getTipoIconoAttribute(): string
    {
        if ($this->esEntrada()) {
            return '↑';
        }

        return '↓';
    }

    /**
     * Obtener la cantidad con signo (+/-)
     */
    public function getCantidadConSignoAttribute(): string
    {
        if ($this->esEntrada()) {
            return '+'.$this->cantidad;
        }

        return '-'.$this->cantidad;
    }
}
