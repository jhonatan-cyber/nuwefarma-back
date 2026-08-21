<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Map de columnas para siat_sesiones
 *
 * @property-read string $id
 * @property-read ?string $punto_venta_id
 * @property-read string $tipo
 * @property-read string $codigo
 * @property-read ?string $codigo_control
 * @property-read ?string $descripcion
 * @property-read ?\Illuminate\Support\Carbon $fecha_vigencia
 * @property-read ?\Illuminate\Support\Carbon $fecha_inicio
 * @property-read ?\Illuminate\Support\Carbon $fecha_fin
 * @property-read string $estado
 * @property-read ?\Illuminate\Support\Carbon $created_at
 * @property-read ?\Illuminate\Support\Carbon $updated_at
 */
class SiatSesion extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'siat_sesiones';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public const TIPO_CUIS = 'cuis';

    public const TIPO_CUFD = 'cufd';

    public const ESTADO_ACTIVA = 'activa';

    public const ESTADO_INACTIVA = 'inactiva';

    public function puntoVenta(): BelongsTo
    {
        return $this->belongsTo(PuntoVenta::class, 'punto_venta_id');
    }

    /**
     * Sesión vigente (CUIS o CUFD) para un punto de venta.
     */
    public static function vigente(string $puntoVentaId, string $tipo): ?self
    {
        return self::where('punto_venta_id', $puntoVentaId)
            ->where('tipo', $tipo)
            ->where('estado', self::ESTADO_ACTIVA)
            ->latest()
            ->first();
    }
}