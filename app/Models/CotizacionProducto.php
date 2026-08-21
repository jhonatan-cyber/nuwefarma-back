<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Map de columnas para cotizacion_productos
 *
 * @property-read string $id
 * @property-read string $cotizacion_id
 * @property-read string $producto_id
 * @property-read int $cantidad
 * @property-read string $precio_unitario
 * @property-read ?\Illuminate\Support\Carbon $created_at
 * @property-read ?\Illuminate\Support\Carbon $updated_at
 */
class CotizacionProducto extends Model
{
    use HasUuids;

    protected $table = 'cotizacion_productos';

    protected $fillable = [
        'cotizacion_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
    ];

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
