<?php

namespace App\Models;

use App\Models\Concerns\HasEstado;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Map de columnas para pacientes
 *
 * @property-read string $id
 * @property-read ?string $ci
 * @property-read string $nombres
 * @property-read ?string $apellidos
 * @property-read ?\Illuminate\Support\Carbon $fecha_nacimiento
 * @property-read ?string $sexo
 * @property-read ?string $telefono
 * @property-read ?string $email
 * @property-read ?string $contacto_emergencia
 * @property-read ?string $observaciones
 * @property-read string $estado
 * @property-read ?string $sucursal_id
 * @property-read ?\Illuminate\Support\Carbon $created_at
 * @property-read ?\Illuminate\Support\Carbon $updated_at
 * @property-read string|null $nombre_completo
 * @property-read string|null $edad
 */
class Paciente extends Model
{
    use HasEstado, HasFactory, HasUuids;

    protected $table = 'pacientes';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $fillable = [
        'ci',
        'nombres',
        'apellidos',
        'fecha_nacimiento',
        'sexo',
        'telefono',
        'email',
        'contacto_emergencia',
        'observaciones',
        'estado',
        'sucursal_id',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['nombre_completo', 'edad'];

    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombres.' '.$this->apellidos);
    }

    public function getEdadAttribute(): ?int
    {
        return $this->fecha_nacimiento?->age;
    }

    public function recetas(): HasMany
    {
        return $this->hasMany(Receta::class, 'paciente_id');
    }
}