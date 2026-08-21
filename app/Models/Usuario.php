<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

/**
 * Map de columnas para usuarios
 *
 * @property-read string $id
 * @property-read string $nombre
 * @property-read string $apellidos
 * @property-read ?string $direccion
 * @property-read string $telefono
 * @property-read string $email
 * @property-read ?\Illuminate\Support\Carbon $email_verified_at
 * @property-read string $password
 * @property-read ?string $remember_token
 * @property-read string $rol_id
 * @property-read ?string $sueldo
 * @property-read string $foto
 * @property-read string $estado
 * @property-read ?\Illuminate\Support\Carbon $created_at
 * @property-read ?\Illuminate\Support\Carbon $updated_at
 * @property-read string $ci
 * @property-read int $intentos_fallidos
 * @property-read ?\Illuminate\Support\Carbon $bloqueado_hasta
 * @property-read ?\Illuminate\Support\Carbon $ultimo_intento_fallido
 * @property-read ?string $sucursal_id
 */
class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    protected $table = 'usuarios';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [
        'id',
        'intentos_fallidos',
        'bloqueado_hasta',
        'ultimo_intento_fallido',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'nombre',
        'apellidos',
        'ci',
        'direccion',
        'telefono',
        'email',
        'password',
        'rol_id',
        'sucursal_id',
        'sueldo',
        'foto',
        'estado',
        'intentos_fallidos',
        'bloqueado_hasta',
        'ultimo_intento_fallido',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'sueldo' => 'decimal:2',
        'bloqueado_hasta' => 'datetime',
        'ultimo_intento_fallido' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot method para auto-hashear el CI como password
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($usuario) {
            if (isset($usuario->ci) && ! isset($usuario->password)) {
                $usuario->password = Hash::make($usuario->ci);
            }
        });

        static::updating(function ($usuario) {
            // Si se actualiza el CI, actualizar también el password
            if ($usuario->isDirty('ci')) {
                $usuario->password = Hash::make($usuario->ci);
            }
        });
    }

    /**
     * Relación con Rol
     */
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    /**
     * Relación con Sucursal
     */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    /**
     * Relación con Ventas realizadas
     */
    public function ventas()
    {
        return $this->hasMany(\App\Models\Venta::class, 'usuario_id');
    }

    /**
     * Relación con Compras realizadas
     */
    public function compras()
    {
        return $this->hasMany(\App\Models\Compra::class, 'usuario_id');
    }

    /**
     * Relación con Sucursales que gerencia
     */
    public function sucursalesGerenciadas()
    {
        return $this->hasMany(Sucursal::class, 'gerente_id');
    }

    /**
     * Verificar si el usuario tiene un rol específico
     *
     * @param string $roleName
     * @return bool
     */
    public function hasRole(string $roleName): bool
    {
        if (!$this->rol) {
            return false;
        }

        return strtolower($this->rol->nombre) === strtolower($roleName);
    }

    /**
     * Verificar si el usuario tiene alguno de los roles especificados
     *
     * @param array $roles
     * @return bool
     */
    public function hasAnyRole(array $roles): bool
    {
        if (!$this->rol) {
            return false;
        }

        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verificar si el usuario tiene todos los roles especificados
     *
     * @param array $roles
     * @return bool
     */
    public function hasAllRoles(array $roles): bool
    {
        if (!$this->rol) {
            return false;
        }

        foreach ($roles as $role) {
            if (!$this->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Obtener el nombre del rol del usuario
     *
     * @return string|null
     */
    public function getRoleName(): ?string
    {
        return $this->rol?->nombre;
    }

    /**
     * Verificar si el usuario es administrador
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('Administrador');
    }

    /**
     * Verificar si el usuario es gerente
     *
     * @return bool
     */
    public function isGerente(): bool
    {
        return $this->hasRole('Gerente');
    }

    /**
     * Verificar si el usuario es cajero
     *
     * @return bool
     */
    public function isCajero(): bool
    {
        return $this->hasRole('Cajero');
    }

    /**
     * Verificar si el usuario es vendedor
     *
     * @return bool
     */
    public function isVendedor(): bool
    {
        return $this->hasRole('Vendedor');
    }
}
