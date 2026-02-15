<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacion extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'notificaciones';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'tipo',
        'titulo',
        'mensaje',
        'modulo',
        'registro_id',
        'estado',
        'usuario_id',
        'data',
        'leido_at',
    ];

    protected $casts = [
        'data' => 'array',
        'leido_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const TIPO_STOCK_BAJO = 'stock_bajo';

    public const TIPO_PROXIMO_VENCER = 'proximo_vencer';

    public const TIPO_VENCIDO = 'vencido';

    public const TIPO_ALERTA_SISTEMA = 'alerta_sistema';

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_LEIDO = 'leido';

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function marcarComoLeida(): void
    {
        $this->update([
            'estado' => self::ESTADO_LEIDO,
            'leido_at' => now(),
        ]);
    }

    public function esLeida(): bool
    {
        return $this->estado === self::ESTADO_LEIDO;
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopePorUsuario($query, string $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    public function scopeSinUsuario($query)
    {
        return $query->whereNull('usuario_id');
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function getIconoAttribute(): string
    {
        return match ($this->tipo) {
            self::TIPO_STOCK_BAJO => '⚠️',
            self::TIPO_PROXIMO_VENCER => '⏰',
            self::TIPO_VENCIDO => '❌',
            self::TIPO_ALERTA_SISTEMA => '🔔',
            default => '📢',
        };
    }

    public function getColorAttribute(): string
    {
        return match ($this->tipo) {
            self::TIPO_STOCK_BAJO => 'yellow',
            self::TIPO_PROXIMO_VENCER => 'orange',
            self::TIPO_VENCIDO => 'red',
            self::TIPO_ALERTA_SISTEMA => 'blue',
            default => 'gray',
        };
    }
}
