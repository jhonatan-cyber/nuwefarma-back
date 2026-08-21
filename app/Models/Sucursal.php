<?php

namespace App\Models;

use App\Models\Concerns\HasAuditoria;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Map de columnas para sucursals
 *
 * @property-read string $id
 * @property-read string $nombre
 * @property-read string $direccion
 * @property-read ?string $ciudad
 * @property-read ?string $telefono
 * @property-read ?string $email
 * @property-read ?string $descripcion
 * @property-read string $estado
 * @property-read ?\Illuminate\Support\Carbon $created_at
 * @property-read ?\Illuminate\Support\Carbon $updated_at
 * @property-read ?string $gerente_id
 * @property-read ?string $pais
 * @property-read ?string $crear_usuario_id
 * @property-read ?string $actualizar_usuario_id
 * @property-read string|null $creador_nombre
 * @property-read string|null $actualizador_nombre
 * @property-read array $auditoria
 */
class Sucursal extends Model
{
    use HasAuditoria, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'nombre',
        'direccion',
        'ciudad',
        'pais',
        'telefono',
        'email',
        'gerente_id',
        'descripcion',
        'estado',
    ];

    protected $casts = [
        'estado' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con Usuarios
     */
    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'sucursal_id');
    }

    /**
     * Relación con Usuario Gerente
     */
    public function gerente(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'gerente_id');
    }
}
