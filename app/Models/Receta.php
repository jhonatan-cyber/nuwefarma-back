<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Map de columnas para recetas
 *
 * @property-read string $id
 * @property-read string $numero_receta
 * @property-read \Illuminate\Support\Carbon $fecha_emision
 * @property-read ?\Illuminate\Support\Carbon $fecha_vencimiento
 * @property-read ?string $observaciones
 * @property-read string $estado
 * @property-read ?string $medico_id
 * @property-read ?string $paciente_id
 * @property-read ?string $usuario_id
 * @property-read ?string $sucursal_id
 * @property-read ?\Illuminate\Support\Carbon $created_at
 * @property-read ?\Illuminate\Support\Carbon $updated_at
 */
class Receta extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'recetas';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $fillable = [
        'numero_receta',
        'fecha_emision',
        'fecha_vencimiento',
        'observaciones',
        'estado',
        'medico_id',
        'paciente_id',
        'usuario_id',
        'sucursal_id',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_PARCIAL = 'parcial';

    public const ESTADO_ATENDIDA = 'atendida';

    public const ESTADO_VENCIDA = 'vencida';

    public const ESTADO_ANULADA = 'anulada';

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'medico_id');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function productos(): HasMany
    {
        return $this->hasMany(RecetaProducto::class, 'receta_id');
    }

    /**
     * Generar número de receta automático.
     */
    public static function generateNumeroReceta(): string
    {
        $last = self::orderBy('created_at', 'desc')->first();
        $lastNumber = $last ? (int) substr($last->numero_receta, 3) : 0;

        return 'REC-'.str_pad((string) ($lastNumber + 1), 8, '0', STR_PAD_LEFT);
    }

    /**
     * Recalcular el estado de la receta según las cantidades dispensadas.
     */
    public function recalcularEstado(): void
    {
        if ($this->estado === self::ESTADO_ANULADA) {
            return;
        }

        $items = $this->productos()->get();

        if ($items->isEmpty()) {
            return;
        }

        if ($items->every(fn (RecetaProducto $item) => (float) $item->cantidad_dispensada >= (float) $item->cantidad_prescrita)) {
            $this->estado = self::ESTADO_ATENDIDA;
        } elseif ($items->sum(fn (RecetaProducto $item) => $item->cantidad_dispensada) > 0) {
            $this->estado = self::ESTADO_PARCIAL;
        } else {
            $this->estado = self::ESTADO_PENDIENTE;
        }

        $this->save();
    }
}