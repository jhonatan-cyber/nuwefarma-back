<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasAuditoria;
use App\Models\Concerns\ScopedBySucursal;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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