<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Relación manual de equivalencia / sustitución entre productos.
 *
 * No automatiza ninguna decisión clínica: solo documenta que el operador
 * considera al producto equivalente como reemplazo, con factor de conversión
 * cuando las presentaciones difieren.
 */
class EquivalenciaProducto extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'equivalencia_productos';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $fillable = [
        'producto_id',
        'producto_equivalente_id',
        'tipo',
        'factor_conversion',
        'notas',
        'estado',
    ];

    protected $casts = [
        'factor_conversion' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const TIPO_EQUIVALENTE = 'equivalente';

    public const TIPO_GENERICO = 'generico';

    public const TIPO_SUSTITUTO = 'sustituto';

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function productoEquivalente(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_equivalente_id');
    }

    public function getTipoLabelAttribute(): string
    {
        return match ($this->tipo) {
            self::TIPO_GENERICO => 'Genérico',
            self::TIPO_SUSTITUTO => 'Sustituto',
            default => 'Equivalente',
        };
    }
}