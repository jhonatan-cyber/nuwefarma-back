<?php

namespace App\Models;

use App\Models\Concerns\HasAuditoria;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Map de columnas para proveedores
 *
 * @property-read string $id
 * @property-read string $nombre
 * @property-read ?string $nit
 * @property-read ?string $telefono
 * @property-read ?string $email
 * @property-read ?string $direccion
 * @property-read ?string $ciudad
 * @property-read ?string $pais
 * @property-read ?string $contacto
 * @property-read ?string $telefono_contacto
 * @property-read ?string $categoria
 * @property-read ?string $observaciones
 * @property-read string $estado
 * @property-read ?\Illuminate\Support\Carbon $created_at
 * @property-read ?\Illuminate\Support\Carbon $updated_at
 * @property-read ?string $crear_usuario_id
 * @property-read ?string $actualizar_usuario_id
 * @property-read string|null $creador_nombre
 * @property-read string|null $actualizador_nombre
 * @property-read array $auditoria
 */
class Proveedor extends Model
{
    use HasAuditoria, HasFactory, HasUuids;

    protected $table = 'proveedores';

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
        'nit',
        'telefono',
        'email',
        'direccion',
        'ciudad',
        'pais',
        'contacto',
        'telefono_contacto',
        'categoria',
        'observaciones',
        'estado',
    ];

    public function productos(): HasMany
    {
        return $this->hasMany(\App\Models\Producto::class, 'proveedor_id', 'id');
    }

    public function compras(): HasMany
    {
        return $this->hasMany(\App\Models\Compra::class, 'proveedor_id', 'id');
    }

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
