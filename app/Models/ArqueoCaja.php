<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasAuditoria;
use App\Models\Concerns\ScopedBySucursal;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArqueoCaja extends Model
{
    use ScopedBySucursal;
    use HasAuditoria, HasFactory, HasUuids;

    protected $table = 'arqueos_caja';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'numero_arqueo',
        'caja_id',
        'saldo_inicial',
        'total_ingresos',
        'total_egresos',
        'saldo_sistema',
        'total_declarado',
        'total_contado',
        'diferencia',
        'detalles',
        'estado',
        'observaciones',
        'fecha_cierre',
        'usuario_id',
        'sucursal_id',
    ];

    protected $casts = [
        'saldo_inicial' => 'decimal:2',
        'total_ingresos' => 'decimal:2',
        'total_egresos' => 'decimal:2',
        'saldo_sistema' => 'decimal:2',
        'total_declarado' => 'decimal:2',
        'total_contado' => 'decimal:2',
        'diferencia' => 'decimal:2',
        'detalles' => 'array',
        'fecha_cierre' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const ESTADO_REALIZADO = 'realizado';

    public const ESTADO_CONCILIADO = 'conciliado';

    /**
     * Relación con la caja arqueada.
     */
    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'caja_id');
    }

    /**
     * Relación con el usuario que realizó el arqueo.
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
     * Generar número de arqueo automático.
     */
    public static function generateNumeroArqueo(): string
    {
        $last = self::orderBy('created_at', 'desc')->first();
        $lastNumber = $last ? (int) substr($last->numero_arqueo, 5) : 0;

        return 'ARQ-'.str_pad((string) ($lastNumber + 1), 8, '0', STR_PAD_LEFT);
    }
}