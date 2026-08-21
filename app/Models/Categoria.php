<?php

namespace App\Models;

use App\Models\Concerns\HasAuditoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Map de columnas para categorias
 *
 * @property-read string $id
 * @property-read string $nombre
 * @property-read ?string $descripcion
 * @property-read string $estado
 * @property-read ?\Illuminate\Support\Carbon $created_at
 * @property-read ?\Illuminate\Support\Carbon $updated_at
 * @property-read ?string $crear_usuario_id
 * @property-read ?string $actualizar_usuario_id
 * @property-read string|null $creador_nombre
 * @property-read string|null $actualizador_nombre
 * @property-read array $auditoria
 */
class Categoria extends Model
{
    use HasAuditoria, HasFactory, HasUuids;

    protected $table = 'categorias';

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
        'estado',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación inversa: una categoría tiene muchos productos
     */
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'categoria_id');
    }

    /**
     * Scope para filtrar categorías activas
     */
    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('estado', 'activo');
    }

    /**
     * Scope para filtrar categorías inactivas
     */
    public function scopeInactivas(Builder $query): Builder
    {
        return $query->where('estado', 'inactivo');
    }
}
