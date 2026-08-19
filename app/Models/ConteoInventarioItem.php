<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConteoInventarioItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'conteo_inventario_items';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $fillable = [
        'conteo_id',
        'producto_id',
        'lote_id',
        'stock_sistema',
        'stock_fisico',
        'diferencia',
        'estado',
        'observaciones',
        'contado_por_id',
    ];

    protected $casts = [
        'stock_sistema' => 'integer',
        'stock_fisico' => 'integer',
        'diferencia' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_CONTADO = 'contado';

    public function conteo(): BelongsTo
    {
        return $this->belongsTo(ConteoInventario::class, 'conteo_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'lote_id');
    }

    public function contadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'contado_por_id');
    }
}