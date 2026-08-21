<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Map de columnas para lotes
 *
 * @property-read string $id
 * @property-read string $producto_id
 * @property-read string $numero_lote
 * @property-read \Illuminate\Support\Carbon $fecha_vencimiento
 * @property-read int $stock
 * @property-read int $stock_comprometido
 * @property-read int $stock_minimo
 * @property-read ?int $stock_maximo
 * @property-read string $precio_costo
 * @property-read string $precio_costo_promedio
 * @property-read string $estado
 * @property-read ?string $proveedor_id
 * @property-read ?string $compra_id
 * @property-read ?string $documento_origen
 * @property-read ?string $ubicacion_bodega
 * @property-read \Illuminate\Support\Carbon $fecha_ingreso
 * @property-read ?\Illuminate\Support\Carbon $fecha_alerta_vencimiento
 * @property-read ?string $notas
 * @property-read ?\Illuminate\Support\Carbon $created_at
 * @property-read ?\Illuminate\Support\Carbon $updated_at
 * @property-read ?\Illuminate\Support\Carbon $deleted_at
 */
class Lote extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'lotes';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [
        'id',
        'stock_comprometido',
        'precio_costo_promedio',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'producto_id',
        'numero_lote',
        'fecha_vencimiento',
        'stock',
        'stock_comprometido',
        'stock_minimo',
        'stock_maximo',
        'precio_costo',
        'precio_costo_promedio',
        'estado',
        'proveedor_id',
        'compra_id',
        'documento_origen',
        'ubicacion_bodega',
        'fecha_ingreso',
        'fecha_alerta_vencimiento',
        'notas',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'fecha_ingreso' => 'date',
        'fecha_alerta_vencimiento' => 'date',
        'stock' => 'integer',
        'stock_comprometido' => 'integer',
        'stock_minimo' => 'integer',
        'stock_maximo' => 'integer',
        'precio_costo' => 'decimal:2',
        'precio_costo_promedio' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con Producto
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    /**
     * Relación con Proveedor
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    /**
     * Relación con Usuario (responsable del registro)
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Relación con Movimientos (Kardex)
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoLote::class, 'lote_id')->orderBy('created_at', 'desc');
    }

    /**
     * Scope para lotes disponibles (con stock)
     */
    public function scopeDisponibles(Builder $query): Builder
    {
        return $query->where('stock', '>', 0)
            ->where('estado', 'disponible')
            ->where('fecha_vencimiento', '>', now());
    }

    /**
     * Scope para lotes próximos a vencer
     */
    public function scopeProximosAVencer(Builder $query, int $dias = 60): Builder
    {
        return $query->where('fecha_vencimiento', '<=', now()->addDays($dias))
            ->where('fecha_vencimiento', '>', now())
            ->where('stock', '>', 0);
    }

    /**
     * Scope para lotes vencidos
     */
    public function scopeVencidos(Builder $query): Builder
    {
        return $query->where('fecha_vencimiento', '<', now())
            ->where('stock', '>', 0);
    }

    /**
     * Scope para lotes por producto
     */
    public function scopePorProducto(Builder $query, string $productoId): Builder
    {
        return $query->where('producto_id', $productoId);
    }

    /**
     * Scope para ordenar por FEFO (First Expired, First Out)
     */
    public function scopeFefo(Builder $query): Builder
    {
        return $query->orderBy('fecha_vencimiento', 'asc');
    }

    /**
     * Scope para lotes con stock bajo
     */
    public function scopeStockBajo(Builder $query): Builder
    {
        return $query->whereColumn('stock', '<=', 'stock_minimo');
    }

    /**
     * Scope para lotes activos (no vencidos ni retirados)
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->whereIn('estado', ['disponible', 'parcial'])
            ->where('fecha_vencimiento', '>', now());
    }

    /**
     * Verificar si el lote está disponible para venta
     */
    public function estaDisponible(): bool
    {
        return $this->stock > 0
            && $this->estado !== 'vencido'
            && $this->estado !== 'retirado'
            && $this->fecha_vencimiento->isFuture();
    }

    /**
     * Verificar si está próximo a vencer
     */
    public function estaProximoAVencer(int $dias = 60): bool
    {
        return $this->fecha_vencimiento->isFuture()
            && $this->fecha_vencimiento->lte(now()->addDays($dias));
    }

    /**
     * Verificar si está vencido
     */
    public function estaVencido(): bool
    {
        return $this->fecha_vencimiento->isPast();
    }

    /**
     * Obtener stock disponible para venta
     */
    public function getStockDisponibleAttribute(): int
    {
        return max(0, $this->stock - $this->stock_comprometido);
    }

    /**
     * Calcular días restantes para vencimiento
     */
    public function getDiasParaVencerAttribute(): ?int
    {
        if ($this->estaVencido()) {
            return null;
        }

        return now()->diffInDays($this->fecha_vencimiento);
    }

    /**
     * Obtener el estado formateado para visualización
     */
    public function getEstadoFormateadoAttribute(): string
    {
        if ($this->estaVencido()) {
            return 'Vencido';
        }

        return ucfirst($this->estado);
    }

    /**
     * Obtener clase CSS según el estado
     */
    public function getEstadoClassAttribute(): string
    {
        if ($this->estaVencido()) {
            return 'text-red-600 bg-red-100';
        }
        if ($this->estaProximoAVencer(30)) {
            return 'text-orange-600 bg-orange-100';
        }
        if ($this->stock <= $this->stock_minimo) {
            return 'text-yellow-600 bg-yellow-100';
        }

        return 'text-green-600 bg-green-100';
    }

    /**
     * Descontar stock (para ventas)
     */
    public function descontarStock(int $cantidad): bool
    {
        if ($cantidad > $this->stock_disponible) {
            return false;
        }
        $this->stock -= $cantidad;
        if ($this->stock == 0) {
            $this->estado = 'agotado';
        } elseif ($this->stock < $this->stock_minimo) {
            $this->estado = 'parcial';
        }

        return $this->save();
    }

    /**
     * Aumentar stock (para devoluciones o ajustes)
     */
    public function aumentarStock(int $cantidad, ?float $precioCosto = null): bool
    {
        $this->stock += $cantidad;

        // Actualizar precio promedio ponderado
        if ($precioCosto !== null && $precioCosto > 0) {
            $totalCosto = ($this->stock - $cantidad) * $this->precio_costo_promedio + $cantidad * $precioCosto;
            $this->precio_costo_promedio = $totalCosto / $this->stock;
        }

        if ($this->stock > 0 && $this->estado === 'agotado') {
            $this->estado = $this->stock <= $this->stock_minimo ? 'parcial' : 'disponible';
        }

        return $this->save();
    }

    /**
     * Comprometer stock (para reserva en ventas)
     */
    public function comprometerStock(int $cantidad): bool
    {
        if ($cantidad > $this->stock_disponible) {
            return false;
        }
        $this->stock_comprometido += $cantidad;

        return $this->save();
    }

    /**
     * Liberar stock comprometido
     */
    public function liberarStockComprometido(int $cantidad): bool
    {
        $this->stock_comprometido = max(0, $this->stock_comprometido - $cantidad);

        return $this->save();
    }
}
