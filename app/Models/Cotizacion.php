<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Map de columnas para cotizacions
 *
 * @property-read string $id
 * @property-read string $numero_cotizacion
 * @property-read string $cliente
 * @property-read \Illuminate\Support\Carbon $fecha
 * @property-read \Illuminate\Support\Carbon $fecha_vencimiento
 * @property-read string $subtotal
 * @property-read string $impuesto
 * @property-read string $descuento
 * @property-read string $total
 * @property-read string $estado
 * @property-read ?string $descripcion
 * @property-read ?string $notas
 * @property-read ?\Illuminate\Support\Carbon $created_at
 * @property-read ?\Illuminate\Support\Carbon $updated_at
 */
class Cotizacion extends Model
{
    use HasUuids;

    protected $fillable = [
        'numero_cotizacion',
        'cliente',
        'fecha',
        'fecha_vencimiento',
        'subtotal',
        'impuesto',
        'descuento',
        'total',
        'estado',
        'descripcion',
        'notas',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_vencimiento' => 'date',
        'subtotal' => 'decimal:2',
        'impuesto' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
        'estado' => 'string',
    ];

    public function productos()
    {
        return $this->hasMany(CotizacionProducto::class);
    }

    /**
     * Marcar como expirada si venció estando en estado "en_espera".
     */
    public function marcarVencidaSiCorresponde(): bool
    {
        if ($this->estado === 'en_espera' && $this->fecha_vencimiento && $this->fecha_vencimiento->isPast()) {
            $this->update(['estado' => 'expirada']);

            return true;
        }

        return false;
    }

    /**
     * Marcar todas las cotizaciones vencidas como expiradas (bulk).
     */
    public static function expirarVencidas(): int
    {
        return static::query()
            ->where('estado', 'en_espera')
            ->whereNotNull('fecha_vencimiento')
            ->where('fecha_vencimiento', '<', now())
            ->update(['estado' => 'expirada']);
    }
}
