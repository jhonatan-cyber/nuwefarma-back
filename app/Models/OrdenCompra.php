<?php

namespace App\Models;

use App\Exceptions\ApiException;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdenCompra extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'ordenes_compra';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $fillable = [
        'numero_orden',
        'tipo',
        'prioridad',
        'estado',
        'proveedor_id',
        'sucursal_id',
        'usuario_id',
        'aprobado_por_id',
        'recibido_por_id',
        'fecha_solicitud',
        'fecha_estimada',
        'fecha_recepcion',
        'fecha_aprobacion',
        'fecha_envio',
        'fecha_finalizacion',
        'subtotal',
        'descuento',
        'impuestos',
        'total',
        'notas',
        'motivo_rechazo',
    ];

    protected $casts = [
        'fecha_solicitud' => 'date',
        'fecha_estimada' => 'date',
        'fecha_recepcion' => 'date',
        'fecha_aprobacion' => 'datetime',
        'fecha_envio' => 'datetime',
        'fecha_finalizacion' => 'datetime',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'impuestos' => 'decimal:2',
        'total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const TIPO_SOLICITUD = 'solicitud';

    public const TIPO_ORDEN = 'orden';

    public const PRIORIDAD_BAJA = 'baja';

    public const PRIORIDAD_NORMAL = 'normal';

    public const PRIORIDAD_ALTA = 'alta';

    public const PRIORIDAD_URGENTE = 'urgente';

    public const ESTADO_BORRADOR = 'borrador';

    public const ESTADO_PENDIENTE_APROBACION = 'pendiente_aprobacion';

    public const ESTADO_APROBADA = 'aprobada';

    public const ESTADO_RECHAZADA = 'rechazada';

    public const ESTADO_ENVIADA = 'enviada';

    public const ESTADO_RECIBIDA = 'recibida';

    public const ESTADO_CANCELADA = 'cancelada';

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'aprobado_por_id');
    }

    public function recibidoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'recibido_por_id');
    }

    public function productos(): HasMany
    {
        return $this->hasMany(OrdenCompraProducto::class, 'orden_id');
    }

    public static function generateNumeroOrden(): string
    {
        $last = self::orderBy('created_at', 'desc')->first();
        $lastNumber = $last ? (int) substr($last->numero_orden, 4) : 0;

        return 'OC-'.str_pad((string) ($lastNumber + 1), 8, '0', STR_PAD_LEFT);
    }

    public function recalcularTotales(): void
    {
        $items = $this->productos()->get();
        $subtotal = $items->sum(fn ($item) => (float) $item->subtotal);
        $descuento = $items->sum(fn ($item) => (float) $item->descuento);
        $impuestos = $items->sum(fn ($item) => (float) $item->impuesto);

        $this->subtotal = round($subtotal, 2);
        $this->descuento = round($descuento, 2);
        $this->impuestos = round($impuestos, 2);
        $this->total = round($subtotal - $descuento + $impuestos, 2);
        $this->save();
    }

    public function estaRecibidaTotalmente(): bool
    {
        return $this->productos()->get()->every(
            fn (OrdenCompraProducto $item) => (float) $item->cantidad_recibida >= (float) $item->cantidad
        );
    }

    public function tieneRecepcion(): bool
    {
        return (float) $this->productos()->sum('cantidad_recibida') > 0;
    }

    /**
     * @throws ApiException
     */
    public function verificarTransicion(string $estadoPermitido, string $mensaje): void
    {
        if ($this->estado !== $estadoPermitido) {
            throw ApiException::conflict($mensaje, ['estado' => ["La orden está en estado: {$this->estado}"]]
            );
        }
    }
}