<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasFactory, HasUuids, HasApiTokens, Notifiable;

    protected $table = 'usuarios';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

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
            if (isset($usuario->ci) && !isset($usuario->password)) {
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
}
