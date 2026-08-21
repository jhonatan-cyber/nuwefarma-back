<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Map de columnas para siat_transacciones
 *
 * @property-read string $id
 * @property-read string $uuid_request
 * @property-read string $tipo_operacion
 * @property-read ?string $factura_id
 * @property-read ?string $punto_venta_id
 * @property-read ?string $cuf
 * @property-read string $estado
 * @property-read ?string $codigo_respuesta
 * @property-read ?string $descripcion
 * @property-read ?array $request_payload
 * @property-read ?array $response_payload
 * @property-read ?string $ip_origen
 * @property-read ?\Illuminate\Support\Carbon $created_at
 * @property-read ?\Illuminate\Support\Carbon $updated_at
 */
class SiatTransaccion extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'siat_transacciones';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const OP_SOLICITAR_CUIS = 'solicitar_cuis';

    public const OP_SOLICITAR_CUFD = 'solicitar_cufd';

    public const OP_EMITIR = 'emitir';

    public const OP_ANULAR = 'anular';

    public const OP_CONSULTAR = 'consultar';

    public const OP_REVERSION_ANULACION = 'reversion_anulacion';

    public const ESTADO_EXITO = 'exito';

    public const ESTADO_ERROR = 'error';

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'factura_id');
    }

    public function puntoVenta(): BelongsTo
    {
        return $this->belongsTo(PuntoVenta::class, 'punto_venta_id');
    }

    /**
     * Idempotencia: si ya existe una transacción exitosa para el mismo
     * uuid_request, se retorna sin reprocesar.
     */
    public static function existeExitosa(string $uuidRequest): bool
    {
        return self::where('uuid_request', $uuidRequest)
            ->where('estado', self::ESTADO_EXITO)
            ->exists();
    }
}