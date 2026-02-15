<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AjusteInventarioDetalle extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'ajuste_inventario_detalles';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'ajuste_id',
        'lote_id',
        'stock_anterior',
        'stock_nuevo',
        'diferencia',
    ];

    protected $casts = [
        'stock_anterior' => 'integer',
        'stock_nuevo' => 'integer',
        'diferencia' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function ajuste(): BelongsTo
    {
        return $this->belongsTo(AjusteInventario::class, 'ajuste_id');
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'lote_id');
    }

    public function getEsIncrementoAttribute(): bool
    {
        return $this->diferencia > 0;
    }

    public function getEsDecrementoAttribute(): bool
    {
        return $this->diferencia < 0;
    }
}
