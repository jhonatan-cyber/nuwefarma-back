<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

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
}
