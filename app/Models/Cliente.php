<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Cliente extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'ci',
        'nombre',
        'apellido',
        'telefono',
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
        return $this->nombre . ' ' . $this->apellido;
    }
}