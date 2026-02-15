<?php

namespace App\Models;

use App\Models\Concerns\HasAuditoria;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Compra extends Model
{
    use HasAuditoria, HasFactory, HasUuids;

    protected $table = 'compras';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'numero_compra',
        'subtotal',
        'descuento',
        'impuestos',
        'total',
        'estado',
        'metodo_pago',
        'proveedor_id',
        'usuario_id',
        'sucursal_id',
        'notas',
        'fecha_compra',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'impuestos' => 'decimal:2',
        'total' => 'decimal:2',
        'fecha_compra' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con Proveedor
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    /**
     * Relación con Usuario (comprador)
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Relación con Sucursal
     */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    /**
     * Relación con productos de la compra
     */
    public function productos(): HasMany
    {
        return $this->hasMany(CompraProducto::class, 'compra_id');
    }

    /**
     * Generar número de compra automático
     */
    public static function generateNumeroCompra(): string
    {
        $lastCompra = self::orderBy('created_at', 'desc')->first();
        $lastNumber = $lastCompra ? (int) substr($lastCompra->numero_compra, 2) : 0;

        return 'C-'.str_pad($lastNumber + 1, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Calcular totales de la compra
     */
    public function calcularTotales(): void
    {
        $subtotal = $this->productos()->sum('subtotal');
        $this->subtotal = $subtotal;
        $this->total = $subtotal - $this->descuento + $this->impuestos;
        $this->save();
    }

    /**
     * Verificar si la compra puede ser cancelada
     */
    public function puedeSerCancelada(): bool
    {
        return in_array($this->estado, ['pendiente', 'recibida']);
    }

    /**
     * Cancelar compra
     */
    public function cancelar(): void
    {
        if ($this->puedeSerCancelada()) {
            $this->estado = 'cancelada';
            $this->save();

            // Registrar actividad
            ActivityLog::create([
                'usuario_id' => auth()->id(),
                'accion' => 'cancelar_compra',
                'descripcion' => "Compra {$this->numero_compra} cancelada",
                'modulo' => 'Compra',
                'registro_id' => $this->id,
            ]);
        }
    }

    /**
     * Marcar compra como recibida
     */
    public function recibir(): void
    {
        if ($this->estado === 'pendiente') {
            DB::transaction(function () {
                foreach ($this->productos as $producto) {
                    $lote = null;

                    if ($producto->lote_id) {
                        $lote = Lote::find($producto->lote_id);
                    }

                    if (! $lote) {
                        $lote = Lote::create([
                            'producto_id' => $producto->producto_id,
                            'numero_lote' => 'LOTE-'.strtoupper(substr($this->numero_compra, 2)).'-'.str_pad($producto->cantidad, 4, '0', STR_PAD_LEFT),
                            'stock' => $producto->cantidad,
                            'precio_costo' => $producto->precio_unitario,
                            'precio_venta' => $producto->producto->precio_venta ?? $producto->precio_unitario * 1.2,
                            'fecha_fabricacion' => now(),
                            'fecha_vencimiento' => now()->addYear(),
                        ]);

                        $producto->lote_id = $lote->id;
                        $producto->save();
                    } else {
                        $lote->stock += $producto->cantidad;
                        $lote->save();
                    }

                    MovimientoLote::create([
                        'lote_id' => $lote->id,
                        'tipo' => 'entrada_compra',
                        'cantidad' => $producto->cantidad,
                        'stock_anterior' => $lote->stock - $producto->cantidad,
                        'stock_nuevo' => $lote->stock,
                        'precio_unitario' => $producto->precio_unitario,
                        'referencia_id' => $this->id,
                        'referencia_type' => Compra::class,
                        'notas' => "Recepción de compra {$this->numero_compra}",
                    ]);
                }

                $this->estado = 'recibida';
                $this->save();

                ActivityLog::create([
                    'usuario_id' => auth()->id(),
                    'accion' => 'recibir_compra',
                    'descripcion' => "Compra {$this->numero_compra} marcada como recibida",
                    'modulo' => 'Compra',
                    'registro_id' => $this->id,
                ]);
            });
        }
    }

    /**
     * Procesar devolución de compra
     */
    public function devolver(): void
    {
        if ($this->estado === 'recibida') {
            $this->estado = 'devuelta';
            $this->save();

            // Registrar actividad
            ActivityLog::create([
                'usuario_id' => auth()->id(),
                'accion' => 'devolver_compra',
                'descripcion' => "Compra {$this->numero_compra} procesada como devolución",
                'modulo' => 'Compra',
                'registro_id' => $this->id,
            ]);
        }
    }

    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($compra) {
            if (empty($compra->numero_compra)) {
                $compra->numero_compra = self::generateNumeroCompra();
            }
        });

        static::created(function ($compra) {
            // Registrar actividad
            ActivityLog::create([
                'usuario_id' => auth()->id(),
                'accion' => 'crear_compra',
                'descripcion' => "Compra {$compra->numero_compra} creada",
                'modulo' => 'Compra',
                'registro_id' => $compra->id,
            ]);
        });

        static::updated(function ($compra) {
            // Registrar actividad
            ActivityLog::create([
                'usuario_id' => auth()->id(),
                'accion' => 'actualizar_compra',
                'descripcion' => "Compra {$compra->numero_compra} actualizada",
                'modulo' => 'Compra',
                'registro_id' => $compra->id,
            ]);
        });

        static::deleted(function ($compra) {
            // Registrar actividad
            ActivityLog::create([
                'usuario_id' => auth()->id(),
                'accion' => 'eliminar_compra',
                'descripcion' => "Compra {$compra->numero_compra} eliminada",
                'modulo' => 'Compra',
                'registro_id' => $compra->id,
            ]);
        });
    }
}
