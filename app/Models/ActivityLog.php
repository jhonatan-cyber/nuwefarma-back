<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Map de columnas para activity_logs
 *
 * @property-read ?string $usuario_id
 * @property-read string $accion
 * @property-read ?string $modulo
 * @property-read ?string $registro_id
 * @property-read ?string $descripcion
 * @property-read ?array $datos_anteriores
 * @property-read ?array $datos_nuevos
 * @property-read ?string $ip_address
 * @property-read ?string $user_agent
 * @property-read ?\Illuminate\Support\Carbon $created_at
 * @property-read ?\Illuminate\Support\Carbon $updated_at
 */
class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'usuario_id',
        'accion',
        'modulo',
        'registro_id',
        'descripcion',
        'datos_anteriores',
        'datos_nuevos',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con Usuario
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Método helper para registrar actividad
     */
    public static function registrar(
        string $accion,
        ?string $modulo = null,
        ?string $registroId = null,
        ?string $descripcion = null,
        ?array $datosAnteriores = null,
        ?array $datosNuevos = null,
        ?string $usuarioId = null
    ): void {
        $request = request();

        self::create([
            'usuario_id' => $usuarioId ?? auth('sanctum')->id(),
            'accion' => $accion,
            'modulo' => $modulo,
            'registro_id' => $registroId,
            'descripcion' => $descripcion,
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos' => $datosNuevos,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
