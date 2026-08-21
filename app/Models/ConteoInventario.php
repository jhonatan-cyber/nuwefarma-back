<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Map de columnas para conteos_inventario
 *
 * @property-read string $id
 * @property-read string $numero_conteo
 * @property-read ?string $sucursal_id
 * @property-read string $tipo
 * @property-read string $estado
 * @property-read ?\Illuminate\Support\Carbon $fecha_programada
 * @property-read ?\Illuminate\Support\Carbon $fecha_ejecucion
 * @property-read ?\Illuminate\Support\Carbon $fecha_cierre
 * @property-read int $total_items
 * @property-read int $items_conteados
 * @property-read int $items_con_diferencia
 * @property-read string $total_diferencia
 * @property-read ?string $observaciones
 * @property-read ?string $responsable_id
 * @property-read ?string $cerrado_por_id
 * @property-read ?\Illuminate\Support\Carbon $created_at
 * @property-read ?\Illuminate\Support\Carbon $updated_at
 */
class ConteoInventario extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'conteos_inventario';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $fillable = [
        'numero_conteo',
        'sucursal_id',
        'tipo',
        'estado',
        'fecha_programada',
        'fecha_ejecucion',
        'fecha_cierre',
        'total_items',
        'items_conteados',
        'items_con_diferencia',
        'total_diferencia',
        'observaciones',
        'responsable_id',
        'cerrado_por_id',
    ];

    protected $casts = [
        'fecha_programada' => 'date',
        'fecha_ejecucion' => 'date',
        'fecha_cierre' => 'datetime',
        'total_items' => 'integer',
        'items_conteados' => 'integer',
        'items_con_diferencia' => 'integer',
        'total_diferencia' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const TIPO_FISICO = 'fisico';

    public const TIPO_CICLICO = 'ciclico';

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_EN_PROCESO = 'en_proceso';

    public const ESTADO_CERRADO = 'cerrado';

    public const ESTADO_CANCELADO = 'cancelado';

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'responsable_id');
    }

    public function cerradoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'cerrado_por_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ConteoInventarioItem::class, 'conteo_id');
    }

    public static function generateNumeroConteo(): string
    {
        $last = self::orderBy('created_at', 'desc')->first();
        $lastNumber = $last ? (int) substr($last->numero_conteo, 5) : 0;

        return 'CNT-'.str_pad((string) ($lastNumber + 1), 8, '0', STR_PAD_LEFT);
    }

    public function recalcularResumen(): void
    {
        $items = $this->items()->get();

        $this->total_items = $items->count();
        $this->items_conteados = $items->where('estado', '<>', self::ESTADO_PENDIENTE)->count();
        $this->items_con_diferencia = $items->where('diferencia', '<>', 0)->count();
        $this->total_diferencia = $items->sum('diferencia');

        if ($this->items_conteados > 0) {
            if ($this->estado === self::ESTADO_PENDIENTE || $this->estado === self::ESTADO_EN_PROCESO) {
                $this->estado = self::ESTADO_EN_PROCESO;
            }
        }

        $this->save();
    }
}