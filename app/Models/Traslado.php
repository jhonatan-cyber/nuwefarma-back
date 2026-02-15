<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Traslado extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'traslados';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'numero_traslado',
        'sucursal_origen_id',
        'sucursal_destino_id',
        'usuario_solicita_id',
        'usuario_autoriza_id',
        'usuario_recibe_id',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_ENVIADO = 'enviado';

    public const ESTADO_RECIBIDO = 'recibido';

    public const ESTADO_COMPLETADO = 'completado';

    public const ESTADO_CANCELADO = 'cancelado';

    public function sucursalOrigen(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_origen_id');
    }

    public function sucursalDestino(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_destino_id');
    }

    public function usuarioSolicita(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_solicita_id');
    }

    public function usuarioAutoriza(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_autoriza_id');
    }

    public function usuarioRecibe(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_recibe_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(TrasladoDetalle::class, 'traslado_id');
    }

    public static function generateNumeroTraslado(): string
    {
        $lastTraslado = self::orderBy('created_at', 'desc')->first();
        $lastNumber = $lastTraslado ? (int) substr($lastTraslado->numero_traslado, 2) : 0;

        return 'T-'.str_pad($lastNumber + 1, 8, '0', STR_PAD_LEFT);
    }

    public function scopePorEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopeRecientes($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function puedeSerEnviado(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    public function puedeSerRecibido(): bool
    {
        return $this->estado === self::ESTADO_ENVIADO;
    }

    public function puedeSerCancelado(): bool
    {
        return in_array($this->estado, [self::ESTADO_PENDIENTE, self::ESTADO_ENVIADO]);
    }
}
