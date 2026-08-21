<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasAuditoria;
use App\Models\Concerns\ScopedBySucursal;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Map de columnas para notas_credito
 *
 * @property-read string $id
 * @property-read string $numero
 * @property-read string $documento_tipo
 * @property-read string $documento_id
 * @property-read ?string $documento_numero
 * @property-read string $monto
 * @property-read ?string $motivo
 * @property-read string $estado
 * @property-read ?string $aplicado_a
 * @property-read ?string $usuario_id
 * @property-read ?string $sucursal_id
 * @property-read ?\Illuminate\Support\Carbon $created_at
 * @property-read ?\Illuminate\Support\Carbon $updated_at
 * @property-read ?string $crear_usuario_id
 * @property-read ?string $actualizar_usuario_id
 * @property-read string|null $creador_nombre
 * @property-read string|null $actualizador_nombre
 * @property-read array $auditoria
 */
class NotaCredito extends Model
{
    use ScopedBySucursal;
    use HasAuditoria, HasFactory, HasUuids;

    protected $table = 'notas_credito';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'numero',
        'documento_tipo',
        'documento_id',
        'documento_numero',
        'monto',
        'motivo',
        'estado',
        'aplicado_a',
        'usuario_id',
        'sucursal_id',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const ESTADO_EMITIDA = 'emitida';

    public const ESTADO_APLICADA = 'aplicada';

    public const ESTADO_ANULADA = 'anulada';

    /**
     * Relación con el usuario que emitió la nota.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Relación con la sucursal.
     */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    /**
     * Relación con la venta si la nota corresponde a una venta.
     */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'documento_id');
    }

    /**
     * Relación con la compra si la nota corresponde a una compra.
     */
    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'documento_id');
    }

    /**
     * Generar número de nota de crédito automático.
     */
    public static function generateNumero(): string
    {
        $last = self::orderBy('created_at', 'desc')->first();
        $lastNumber = $last ? (int) substr($last->numero, 3) : 0;

        return 'NC-'.str_pad((string) ($lastNumber + 1), 8, '0', STR_PAD_LEFT);
    }
}