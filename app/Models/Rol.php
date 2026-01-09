<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Rol extends Model
{
    use HasUuids;

    protected $table = 'roles';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

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
}
