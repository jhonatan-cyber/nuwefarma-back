<?php

namespace App\Models;

use App\Models\Concerns\HasAuditoria;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Map de columnas para clientes
 *
 * @property-read string $id
 * @property-read ?string $ci
 * @property-read string $nombre
 * @property-read ?string $apellidos
 * @property-read ?string $telefono
 * @property-read string $estado
 * @property-read ?\Illuminate\Support\Carbon $created_at
 * @property-read ?\Illuminate\Support\Carbon $updated_at
 * @property-read ?string $crear_usuario_id
 * @property-read ?string $actualizar_usuario_id
 * @property-read ?string $nit
 * @property-read ?string $email
 * @property-read ?string $direccion
 * @property-read string|null $creador_nombre
 * @property-read string|null $actualizador_nombre
 * @property-read array $auditoria
 */
class Cliente extends Model
{
    use HasAuditoria, HasFactory, HasUuids;

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'ci',
        'nombre',
        'apellidos',
        'nit',
        'direccion',
        'telefono',
        'celular',
        'email',
        'fecha_nacimiento',
        'sexo',
        'estado_civil',
        'ocupacion',
        'referencia_nombre',
        'referencia_telefono',
        'limite_credito',
        'dias_credito',
        'observaciones',
        'estado',
    ];

    protected $casts = [
        'estado' => 'string',
    ];

    /**
     * Scope para filtrar por estado
     */
    public function scopeActivo($query)
    {
        return $query->where('estado', 'activo');
    }

    /**
     * Scope para filtrar por estado inactivo
     */
    public function scopeInactivo($query)
    {
        return $query->where('estado', 'inactivo');
    }

    /**
     * Accessor para obtener el nombre completo
     */
    public function getNombreCompletoAttribute()
    {
        return $this->nombre.' '.$this->apellidos;
    }

    /**
     * Relación con las ventas del cliente
     */
    public function ventas()
    {
        return $this->hasMany(\App\Models\Venta::class, 'cliente_id');
    }
}
