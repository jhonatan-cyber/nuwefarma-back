<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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