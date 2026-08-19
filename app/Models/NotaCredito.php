<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasAuditoria;
use App\Models\Concerns\ScopedBySucursal;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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