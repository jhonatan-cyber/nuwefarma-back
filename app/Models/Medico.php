<?php

namespace App\Models;

use App\Models\Concerns\HasEstado;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Map de columnas para medicos
 *
 * @property-read string $id
 * @property-read string $nombres
 * @property-read ?string $apellidos
 * @property-read ?string $ci
 * @property-read ?string $registro_profesional
 * @property-read ?string $especialidad
 * @property-read ?string $telefono
 * @property-read ?string $email
 * @property-read ?string $institucion
 * @property-read string $estado
 * @property-read ?\Illuminate\Support\Carbon $created_at
 * @property-read ?\Illuminate\Support\Carbon $updated_at
 * @property-read string|null $nombre_completo
 */
class Medico extends Model
{
    use HasEstado, HasFactory, HasUuids;

    protected $table = 'medicos';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $fillable = [
        'nombres',
        'apellidos',
        'ci',
        'registro_profesional',
        'especialidad',
        'telefono',
        'email',
        'institucion',
        'estado',
    ];

    protected $appends = ['nombre_completo'];

    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombres.' '.$this->apellidos);
    }

    public function recetas(): HasMany
    {
        return $this->hasMany(Receta::class, 'medico_id');
    }
}