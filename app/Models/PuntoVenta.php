<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Map de columnas para puntos_venta
 *
 * @property-read string $id
 * @property-read ?string $sucursal_id
 * @property-read string $codigo_poa
 * @property-read ?string $nombre
 * @property-read ?string $telefono
 * @property-read ?string $direccion
 * @property-read string $tipo
 * @property-read string $ambiente
 * @property-read string $estado
 * @property-read ?\Illuminate\Support\Carbon $created_at
 * @property-read ?\Illuminate\Support\Carbon $updated_at
 */
class PuntoVenta extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'puntos_venta';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $fillable = [
        'sucursal_id',
        'codigo_poa',
        'nombre',
        'telefono',
        'direccion',
        'tipo',
        'ambiente',
        'estado',
    ];

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public static function generarCodigoPoa(): string
    {
        $last = self::orderBy('codigo_poa', 'desc')->first();
        $lastNumber = $last ? (int) $last->codigo_poa : 0;

        return str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    }
}