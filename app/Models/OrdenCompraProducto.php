<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Map de columnas para orden_compra_productos
 *
 * @property-read string $id
 * @property-read string $orden_id
 * @property-read string $producto_id
 * @property-read string $cantidad
 * @property-read string $cantidad_recibida
 * @property-read string $precio_unitario
 * @property-read string $descuento
 * @property-read string $impuesto
 * @property-read string $subtotal
 * @property-read ?string $lote_id
 * @property-read ?\Illuminate\Support\Carbon $fecha_vencimiento
 * @property-read ?\Illuminate\Support\Carbon $created_at
 * @property-read ?\Illuminate\Support\Carbon $updated_at
 */
class OrdenCompraProducto extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'orden_compra_productos';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $fillable = [
        'orden_id',
        'producto_id',
        'cantidad',
        'cantidad_recibida',
        'precio_unitario',
        'descuento',
        'impuesto',
        'subtotal',
        'lote_id',
        'fecha_vencimiento',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'cantidad_recibida' => 'decimal:2',
        'precio_unitario' => 'decimal:4',
        'descuento' => 'decimal:2',
        'impuesto' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'fecha_vencimiento' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'lote_id');
    }

    public function getPendienteRecibir(): float
    {
        return max(0, round((float) $this->cantidad - (float) $this->cantidad_recibida, 2));
    }
}