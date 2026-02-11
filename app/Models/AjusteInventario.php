<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AjusteInventario extends Model
{
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
