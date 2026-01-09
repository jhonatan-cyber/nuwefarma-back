<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Producto extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'productos';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'categoria_id',
        'nombre',
        'codigo_barras',
        'sku',
        'codigo_interno',
        'laboratorio',
        'forma_farmaceutica',
        'concentracion',
        'presentacion',
        'via_administracion',
        'unidad_medida',
        'fracciones_por_unidad',
        'permite_fraccionar',
        'lote',
        'fecha_vencimiento',
        'registro_sanitario',
        'refrigeracion_requerida',
        'dias_para_alertar_vencimiento',
        'stock_actual',
        'stock_minimo',
        'stock_maximo',
        'precio_compra',
        'precio_venta',
        'margen_sugerido',
        'impuesto',
        'etiquetas',
        'fotos',
        'descripcion',
        'estado',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'refrigeracion_requerida' => 'boolean',
        'permite_fraccionar' => 'boolean',
        'dias_para_alertar_vencimiento' => 'integer',
        'stock_actual' => 'integer',
        'stock_minimo' => 'integer',
        'stock_maximo' => 'integer',
        'precio_compra' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'margen_sugerido' => 'decimal:2',
        'impuesto' => 'decimal:2',
        'etiquetas' => 'array',
        'fotos' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con Categoría
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
}
