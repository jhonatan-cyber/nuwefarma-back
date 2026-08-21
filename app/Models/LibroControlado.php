<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Traza regulatoria del libro de movimientos de medicamentos controlados.
 */
/**
 * Map de columnas para libro_controlados
 *
 * @property-read string $id
 * @property-read string $producto_id
 * @property-read ?string $lote_id
 * @property-read ?string $receta_id
 * @property-read ?string $receta_numero
 * @property-read ?string $paciente_id
 * @property-read ?string $medico_id
 * @property-read string $cantidad
 * @property-read ?string $autorizacion
 * @property-read string $tipo_operacion
 * @property-read ?string $observaciones
 * @property-read ?string $cajero_id
 * @property-read ?string $sucursal_id
 * @property-read ?\Illuminate\Support\Carbon $created_at
 * @property-read ?\Illuminate\Support\Carbon $updated_at
 */
class LibroControlado extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'libro_controlados';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $fillable = [
        'producto_id',
        'lote_id',
        'receta_id',
        'receta_numero',
        'paciente_id',
        'medico_id',
        'cantidad',
        'autorizacion',
        'tipo_operacion',
        'observaciones',
        'cajero_id',
        'sucursal_id',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const OPERACION_DISPENSACION = 'dispensacion';

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'lote_id');
    }

    public function receta(): BelongsTo
    {
        return $this->belongsTo(Receta::class, 'receta_id');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'medico_id');
    }

    public function cajero(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'cajero_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }
}