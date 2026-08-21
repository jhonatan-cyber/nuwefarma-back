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
 * Map de columnas para pagos
 *
 * @property-read string $id
 * @property-read string $numero_pago
 * @property-read string $documento_tipo
 * @property-read string $documento_id
 * @property-read ?string $documento_numero
 * @property-read \Illuminate\Support\Carbon $fecha_pago
 * @property-read string $monto
 * @property-read string $metodo_pago
 * @property-read string $tipo_pago
 * @property-read ?string $referencia
 * @property-read ?string $nota
 * @property-read ?string $caja_id
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
class Pago extends Model
{
    use ScopedBySucursal;
    use HasAuditoria, HasFactory, HasUuids;

    protected $table = 'pagos';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'numero_pago',
        'documento_tipo',
        'documento_id',
        'documento_numero',
        'fecha_pago',
        'monto',
        'metodo_pago',
        'tipo_pago',
        'referencia',
        'nota',
        'caja_id',
        'usuario_id',
        'sucursal_id',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_pago' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con la caja donde se registró el pago.
     */
    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'caja_id');
    }

    /**
     * Relación con el usuario que registró el pago.
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
     * Relación con la venta si el documento es una venta.
     */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'documento_id');
    }

    /**
     * Relación con la compra si el documento es una compra.
     */
    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'documento_id');
    }

    /**
     * Generar número de pago automático.
     */
    public static function generateNumeroPago(): string
    {
        $last = self::orderBy('created_at', 'desc')->first();
        $lastNumber = $last ? (int) substr($last->numero_pago, 5) : 0;

        return 'PAGO-'.str_pad((string) ($lastNumber + 1), 8, '0', STR_PAD_LEFT);
    }
}