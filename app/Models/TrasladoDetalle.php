<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrasladoDetalle extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'traslado_detalles';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'traslado_id',
        'lote_origen_id',
        'lote_destino_id',
        'cantidad',
        'recibido',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'recibido' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function traslado(): BelongsTo
    {
        return $this->belongsTo(Traslado::class, 'traslado_id');
    }

    public function loteOrigen(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'lote_origen_id');
    }

    public function loteDestino(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'lote_destino_id');
    }

    public function getEstaCompletoAttribute(): bool
    {
        return $this->recibido >= $this->cantidad;
    }
}
