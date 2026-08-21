<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Map de columnas para facturas
 *
 * @property-read string $id
 * @property-read ?string $venta_id
 * @property-read ?string $punto_venta_id
 * @property-read ?string $sucursal_id
 * @property-read ?string $usuario_id
 * @property-read string $numero_factura
 * @property-read string $cuf
 * @property-read ?string $cufd
 * @property-read ?string $cuis
 * @property-read ?string $numero_autorizacion
 * @property-read ?string $codigo_control
 * @property-read string $tipo_emision
 * @property-read string $tipo_documento_sector
 * @property-read string $tipo_pago
 * @property-read ?string $nit_cliente
 * @property-read ?string $razon_social_cliente
 * @property-read ?string $codigo_cliente
 * @property-read ?string $complemento_ci
 * @property-read ?string $direccion_cliente
 * @property-read ?string $email_cliente
 * @property-read \Illuminate\Support\Carbon $fecha_emision
 * @property-read ?string $leyenda
 * @property-read ?string $numero_serie
 * @property-read ?string $numero_duplicado
 * @property-read string $subtotal
 * @property-read string $descuento
 * @property-read string $monto_total
 * @property-read string $monto_sujeto_iva
 * @property-read string $monto_no_sujeto
 * @property-read string $monto_ice
 * @property-read string $estado
 * @property-read ?string $motivo_anulacion
 * @property-read ?\Illuminate\Support\Carbon $fecha_anulacion
 * @property-read ?string $respuesta_siat
 * @property-read ?string $qr
 * @property-read ?\Illuminate\Support\Carbon $created_at
 * @property-read ?\Illuminate\Support\Carbon $updated_at
 */
class Factura extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'facturas';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_anulacion' => 'datetime',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'monto_total' => 'decimal:2',
        'monto_sujeto_iva' => 'decimal:2',
        'monto_no_sujeto' => 'decimal:2',
        'monto_ice' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const TIPO_EMISION_ONLINE = 'online';

    public const TIPO_EMISION_CONTINGENCIA = 'contingencia';

    public const TIPO_DOC_CON_CREDITO = 'factura_con_credito';

    public const TIPO_DOC_SIN_CREDITO = 'factura_sin_credito';

    public const TIPO_DOC_LIQUIDACION = 'liquidacion';

    public const TIPO_DOC_OTROS = 'otros';

    public const ESTADO_EMITIDA = 'emitida';

    public const ESTADO_ANULADA = 'anulada';

    public const ESTADO_RECHAZADA = 'rechazada';

    public const ESTADO_EN_PROCESO = 'en_proceso';

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function puntoVenta(): BelongsTo
    {
        return $this->belongsTo(PuntoVenta::class, 'punto_venta_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(FacturaDetalle::class, 'factura_id');
    }

    /**
     * Siguiente número de factura consecutivo para el punto de venta.
     */
    public static function siguienteNumero(string $puntoVentaId): int
    {
        return (int) self::where('punto_venta_id', $puntoVentaId)->max('numero_factura') ?? 0;
    }
}