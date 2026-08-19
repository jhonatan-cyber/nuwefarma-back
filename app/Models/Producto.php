<?php

namespace App\Models;

use App\Enums\CondicionVentaEnum;
use App\Enums\ViaAdministracionEnum;
use App\Models\Concerns\HasAuditoria;
use App\Models\Concerns\HasEstado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    use HasAuditoria, HasEstado, HasFactory, HasUuids;

    protected $table = 'productos';

    protected $guarded = [
        'id',
        'crear_usuario_id',
        'actualizar_usuario_id',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'categoria_id',
        'proveedor_id',
        'nombre',
        'principio_activo',
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
        'condicion_venta',
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
        'crear_usuario_id',
        'actualizar_usuario_id',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'refrigeracion_requerida' => 'boolean',
        'permite_fraccionar' => 'boolean',
        'condicion_venta' => CondicionVentaEnum::class,
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

    protected $hidden = [
        'crear_usuario_id',
        'actualizar_usuario_id',
    ];

    protected $appends = [
        'nombre_completo',
        'estado_label',
        'estado_color',
        'precio_con_impuesto',
        'margen_real',
        'bajo_stock',
        'proximo_vencer',
        'via_administracion_label',
        'condicion_venta_label',
        'es_controlado',
        'requiere_receta',
    ];

    // Relationships
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function lotes(): HasMany
    {
        return $this->hasMany(Lote::class, 'producto_id');
    }

    public function ventaProductos(): HasMany
    {
        return $this->hasMany(VentaProducto::class, 'producto_id');
    }

    public function compraProductos(): HasMany
    {
        return $this->hasMany(CompraProducto::class, 'producto_id');
    }

    // Attributes
    protected function nombreCompleto(): Attribute
    {
        return Attribute::make(
            get: fn () => trim("{$this->nombre} {$this->concentracion} {$this->forma_farmaceutica}")
        );
    }

    protected function condicionVentaLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->condicion_venta?->getLabel()
        );
    }

    protected function esControlado(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->condicion_venta?->esControlado() ?? false
        );
    }

    protected function requiereReceta(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->condicion_venta?->requiereReceta() ?? false
        );
    }

    protected function precioConImpuesto(): Attribute
    {
        return Attribute::make(
            get: fn () => round($this->precio_venta * (1 + $this->impuesto / 100), 2)
        );
    }

    protected function margenReal(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->precio_compra > 0
                ? round((($this->precio_venta - $this->precio_compra) / $this->precio_compra) * 100, 2)
                : 0
        );
    }

    protected function bajoStock(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->stock_actual <= $this->stock_minimo
        );
    }

    protected function proximoVencer(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->fecha_vencimiento &&
                $this->fecha_vencimiento->diffInDays(now()) <= ($this->dias_para_alertar_vencimiento ?? 60)
        );
    }

    protected function viaAdministracionLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => ViaAdministracionEnum::tryFrom($this->via_administracion)?->getLabel()
        );
    }

    // Scopes
    public function scopeConStock(Builder $query): Builder
    {
        return $query->where('stock_actual', '>', 0);
    }

    public function scopeSinStock(Builder $query): Builder
    {
        return $query->where('stock_actual', '<=', 0);
    }

    public function scopeBajoStock(Builder $query): Builder
    {
        return $query->whereColumn('stock_actual', '<=', 'stock_minimo');
    }

    public function scopeProximosAVencer(Builder $query, int $dias = 60): Builder
    {
        return $query->whereNotNull('fecha_vencimiento')
            ->where('fecha_vencimiento', '<=', now()->addDays($dias));
    }

    public function scopeVencidos(Builder $query): Builder
    {
        return $query->whereNotNull('fecha_vencimiento')
            ->where('fecha_vencimiento', '<', now());
    }

    public function scopeConRefrigeracion(Builder $query): Builder
    {
        return $query->where('refrigeracion_requerida', true);
    }

    public function scopeFraccionables(Builder $query): Builder
    {
        return $query->where('permite_fraccionar', true);
    }

    public function scopeControlados(Builder $query): Builder
    {
        return $query->where('condicion_venta', CondicionVentaEnum::RECETA_RETENIDA);
    }

    public function scopeConReceta(Builder $query): Builder
    {
        return $query->whereNot('condicion_venta', CondicionVentaEnum::VENTA_LIBRE);
    }

    public function scopeVentaLibre(Builder $query): Builder
    {
        return $query->where('condicion_venta', CondicionVentaEnum::VENTA_LIBRE);
    }

    public function scopePorCategoria(Builder $query, string|int $categoria): Builder
    {
        return $query->where('categoria_id', $categoria);
    }

    public function scopePorProveedor(Builder $query, string|int $proveedor): Builder
    {
        return $query->where('proveedor_id', $proveedor);
    }

    public function scopePorLaboratorio(Builder $query, string $laboratorio): Builder
    {
        return $query->where('laboratorio', 'like', "%{$laboratorio}%");
    }

    public function scopeConPrecioEntre(Builder $query, float $min, float $max): Builder
    {
        return $query->whereBetween('precio_venta', [$min, $max]);
    }

    public function scopeBusqueda(Builder $query, string $termino): Builder
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('nombre', 'like', "%{$termino}%")
                ->orWhere('codigo_barras', 'like', "%{$termino}%")
                ->orWhere('sku', 'like', "%{$termino}%")
                ->orWhere('codigo_interno', 'like', "%{$termino}%")
                ->orWhere('laboratorio', 'like', "%{$termino}%")
                ->orWhere('forma_farmaceutica', 'like', "%{$termino}%");
        });
    }

    // Business Logic Methods
    public function tieneStock(int $cantidad = 1): bool
    {
        return $this->stock_actual >= $cantidad;
    }

    public function descontarStock(int $cantidad): bool
    {
        if (! $this->tieneStock($cantidad)) {
            return false;
        }

        return $this->decrement('stock_actual', $cantidad);
    }

    public function agregarStock(int $cantidad): bool
    {
        return $this->increment('stock_actual', $cantidad);
    }

    public function actualizarPrecio(float $nuevoPrecio, ?string $motivo = null): bool
    {
        $precioAnterior = $this->precio_venta;

        if ($this->update(['precio_venta' => $nuevoPrecio])) {
            // Registrar cambio de precio en activity log
            activity()
                ->causedBy(auth()->user())
                ->performedOn($this)
                ->withProperties([
                    'precio_anterior' => $precioAnterior,
                    'precio_nuevo' => $nuevoPrecio,
                    'motivo' => $motivo,
                ])
                ->log('cambio_precio_producto');

            return true;
        }

        return false;
    }

    public function estaVencido(): bool
    {
        return $this->fecha_vencimiento && $this->fecha_vencimiento->isPast();
    }

    public function diasParaVencer(): ?int
    {
        if (! $this->fecha_vencimiento) {
            return null;
        }

        return $this->fecha_vencimiento->diffInDays(now(), false);
    }

    public function getMargenCalculado(): float
    {
        return $this->precio_compra > 0
            ? (($this->precio_venta - $this->precio_compra) / $this->precio_compra) * 100
            : 0;
    }

    public function getPrecioFinal(): float
    {
        return $this->precio_venta * (1 + $this->impuesto / 100);
    }

    // Query Optimization
    public static function conRelacionesOptimizadas(): Builder
    {
        return static::with(['categoria:id,nombre', 'proveedor:id,nombre']);
    }

    public static function paraInventario(): Builder
    {
        return static::conRelacionesOptimizadas()
            ->withCount('lotes')
            ->withSum('lotes as total_lotes_stock');
    }
}
