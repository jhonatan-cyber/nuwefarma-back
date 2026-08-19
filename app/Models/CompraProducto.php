<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompraProducto extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'compra_productos';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'compra_id',
        'producto_id',
        'lote_id',
        'numero_lote',
        'fecha_vencimiento',
        'cantidad',
        'cantidad_recibida',
        'precio_unitario',
        'descuento_unitario',
        'subtotal',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'cantidad_recibida' => 'integer',
        'precio_unitario' => 'decimal:2',
        'descuento_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'fecha_vencimiento' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con Compra
     */
    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'compra_id');
    }

    /**
     * Relación con Producto
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    /**
     * Relación con Lote
     */
    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'lote_id');
    }

    /**
     * Calcular subtotal automáticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($compraProducto) {
            $compraProducto->subtotal = $compraProducto->cantidad *
                ($compraProducto->precio_unitario - $compraProducto->descuento_unitario);
        });

        static::saved(function ($compraProducto) {
            // Recalcular totales de la compra
            $compraProducto->compra->calcularTotales();
        });

        static::deleted(function ($compraProducto) {
            // Recalcular totales de la compra
            if ($compraProducto->compra) {
                $compraProducto->compra->calcularTotales();
            }
        });
    }
}
