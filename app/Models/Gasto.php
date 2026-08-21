<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Map de columnas para gastos
 *
 * @property-read string $id
 * @property-read string $nombre
 * @property-read string $monto
 * @property-read ?string $descripcion
 * @property-read ?string $notas
 * @property-read \Illuminate\Support\Carbon $fecha
 * @property-read string $estado
 * @property-read ?\Illuminate\Support\Carbon $created_at
 * @property-read ?\Illuminate\Support\Carbon $updated_at
 * @property-read string $categoria
 */
class Gasto extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'gastos';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'nombre',
        'monto',
        'descripcion',
        'categoria',
        'notas',
        'fecha',
        'estado',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
