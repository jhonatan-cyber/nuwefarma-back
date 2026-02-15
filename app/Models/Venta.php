<?php

namespace App\Models;

use App\Models\Concerns\HasAuditoria;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Venta extends Model
{
    use HasAuditoria, HasFactory, HasUuids;

    protected $table = 'ventas';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'numero_venta',
        'subtotal',
        'descuento',
        'impuestos',
        'total',
        'estado',
        'metodo_pago',
        'cliente_id',
        'usuario_id',
        'sucursal_id',
        'caja_id',
        'notas',
        'fecha_venta',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'impuestos' => 'decimal:2',
        'total' => 'decimal:2',
        'fecha_venta' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con Cliente
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Relación con Usuario (vendedor)
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
     * Relación con Caja
     */
    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'caja_id');
    }

    /**
     * Relación con productos de la venta
     */
    public function productos(): HasMany
    {
        return $this->hasMany(VentaProducto::class, 'venta_id');
    }

    /**
     * Generar número de venta automático
     */
    public static function generateNumeroVenta(): string
    {
        $lastVenta = self::orderBy('created_at', 'desc')->first();
        $lastNumber = $lastVenta ? (int) substr($lastVenta->numero_venta, 2) : 0;

        return 'V-'.str_pad($lastNumber + 1, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Calcular totales de la venta
     */
    public function calcularTotales(): void
    {
        $subtotal = $this->productos()->sum('subtotal');
        $this->subtotal = $subtotal;
        $this->total = $subtotal - $this->descuento + $this->impuestos;
        $this->save();
    }

    /**
     * Verificar si la venta puede ser cancelada
     */
    public function puedeSerCancelada(): bool
    {
        return in_array($this->estado, ['pendiente', 'completada']);
    }

    /**
     * Cancelar venta
     */
    public function cancelar(): void
    {
        if ($this->puedeSerCancelada()) {
            $this->estado = 'cancelada';
            $this->save();
        }
    }

    /**
     * Completar venta
     */
    public function completar(): void
    {
        if ($this->estado === 'pendiente') {
            DB::transaction(function () {
                $inventarioService = new \App\Services\InventarioService;

                foreach ($this->productos as $producto) {
                    $inventarioService->descontarStock($producto->producto_id, $producto->cantidad, [
                        'tipo' => 'Venta',
                        'id' => $this->id,
                        'numero' => $this->numero_venta,
                        'observaciones' => "Venta {$this->numero_venta}",
                    ]);
                }

                $this->estado = 'completada';
                $this->save();
            });
        }
    }

    /**
     * Procesar devolución
     */
    public function devolver(): void
    {
        if ($this->estado === 'completada') {
            $this->estado = 'devuelta';
            $this->save();
        }
    }
}
