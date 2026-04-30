<?php

namespace App\Models;

use App\Models\Concerns\HasAuditoria;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
