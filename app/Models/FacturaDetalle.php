<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Map de columnas para factura_detalles
 *
 * @property-read string $id
 * @property-read string $factura_id
 * @property-read ?string $producto_id
 * @property-read ?string $codigo_producto
 * @property-read ?string $descripcion
 * @property-read int $cantidad
 * @property-read string $unidad_medida
 * @property-read string $precio_unitario
 * @property-read string $descuento_unitario
 * @property-read string $subtotal
 * @property-read string $monto_descuento
 * @property-read ?\Illuminate\Support\Carbon $created_at
 * @property-read ?\Illuminate\Support\Carbon $updated_at
 */
class FacturaDetalle extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'factura_detalles';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'factura_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}