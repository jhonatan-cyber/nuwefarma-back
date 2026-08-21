<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Map de columnas para roles
 *
 * @property-read string $id
 * @property-read string $nombre
 * @property-read ?string $descripcion
 * @property-read ?array $permiso_id
 * @property-read string $estado
 * @property-read ?\Illuminate\Support\Carbon $created_at
 * @property-read ?\Illuminate\Support\Carbon $updated_at
 */
class Rol extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'roles';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'nombre',
        'descripcion',
        'permiso_id',
        'estado',
    ];

    protected $casts = [
        'permiso_id' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con usuarios
     */
    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'rol_id');
    }
}
