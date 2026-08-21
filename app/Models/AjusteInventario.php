<?php

namespace App\Models;

use App\Models\Concerns\ScopedBySucursal;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Map de columnas para ajustes_inventario
 *
 * @property-read string $id
 * @property-read string $tipo
 * @property-read string $motivo
 * @property-read ?string $observaciones
 * @property-read string $usuario_id
 * @property-read ?string $sucursal_id
 * @property-read ?\Illuminate\Support\Carbon $created_at
 * @property-read ?\Illuminate\Support\Carbon $updated_at
 */
class AjusteInventario extends Model
{
    use ScopedBySucursal;
    use HasFactory, HasUuids;

    protected $table = 'ajustes_inventario';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'tipo',
        'motivo',
        'observaciones',
        'usuario_id',
        'sucursal_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const TIPO_INCREMENTO = 'incremento';

    public const TIPO_DECREMENTO = 'decremento';

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(AjusteInventarioDetalle::class, 'ajuste_id');
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeRecientes($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function getTotalIncrementoAttribute(): int
    {
        return $this->detalles()->where('diferencia', '>', 0)->sum('diferencia');
    }

    public function getTotalDecrementoAttribute(): int
    {
        return $this->detalles()->where('diferencia', '<', 0)->sum('diferencia');
    }
}
