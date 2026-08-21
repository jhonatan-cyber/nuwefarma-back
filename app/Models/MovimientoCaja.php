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
 * Map de columnas para movimientos_caja
 *
 * @property-read string $id
 * @property-read string $caja_id
 * @property-read string $tipo
 * @property-read string $origen
 * @property-read ?string $documento_tipo
 * @property-read ?string $documento_id
 * @property-read ?string $documento_numero
 * @property-read string $monto
 * @property-read ?string $saldo_despues
 * @property-read ?string $concepto
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
class MovimientoCaja extends Model
{
    use ScopedBySucursal;
    use HasAuditoria, HasFactory, HasUuids;

    protected $table = 'movimientos_caja';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'caja_id',
        'tipo',
        'origen',
        'documento_tipo',
        'documento_id',
        'documento_numero',
        'monto',
        'saldo_despues',
        'concepto',
        'usuario_id',
        'sucursal_id',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'saldo_despues' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const INGRESO = 'ingreso';

    public const EGRESO = 'egreso';

    public const ORIGEN_VENTA = 'venta';

    public const ORIGEN_COMPRA = 'compra';

    public const ORIGEN_ABONO = 'abono';

    public const ORIGEN_PAGO = 'pago';

    public const ORIGEN_GASTO = 'gasto';

    public const ORIGEN_APERTURA = 'apertura';

    public const ORIGEN_AJUSTE_ARQUEO = 'ajuste_arqueo';

    public const ORIGEN_NOTA_CREDITO = 'nota_credito';

    /**
     * Relación con la caja.
     */
    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'caja_id');
    }

    /**
     * Relación con el usuario que registró el movimiento.
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
}