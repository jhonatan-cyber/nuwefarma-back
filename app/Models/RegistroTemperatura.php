<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroTemperatura extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'registros_temperatura';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $fillable = [
        'sucursal_id',
        'tipo_registro',
        'ubicacion',
        'dispositivo',
        'temperatura',
        'humedad',
        'temp_minima_aceptable',
        'temp_maxima_aceptable',
        'dentro_rango',
        'observaciones',
        'usuario_id',
        'registrado_en',
    ];

    protected $casts = [
        'temperatura' => 'decimal:1',
        'humedad' => 'decimal:1',
        'temp_minima_aceptable' => 'decimal:1',
        'temp_maxima_aceptable' => 'decimal:1',
        'dentro_rango' => 'boolean',
        'registrado_en' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}