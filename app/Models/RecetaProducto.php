<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecetaProducto extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'receta_productos';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $fillable = [
        'receta_id',
        'producto_id',
        'cantidad_prescrita',
        'cantidad_dispensada',
        'posologia',
        'duracion',
        'estado',
    ];

    protected $casts = [
        'cantidad_prescrita' => 'decimal:2',
        'cantidad_dispensada' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_PARCIAL = 'parcial';

    public const ESTADO_DISPENSADO = 'dispensado';

    public const ESTADO_ANULADO = 'anulado';

    public function receta(): BelongsTo
    {
        return $this->belongsTo(Receta::class, 'receta_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function getPendiente(): float
    {
        return max(0, round((float) $this->cantidad_prescrita - (float) $this->cantidad_dispensada, 2));
    }
}