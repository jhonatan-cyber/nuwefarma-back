<?php

namespace App\Models;

use App\Exceptions\ApiException;
use App\Models\Concerns\HasAuditoria;
use App\Models\Concerns\ScopedBySucursal;
use App\Services\CajaLibroService;
use App\Services\NotaCreditoService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Map de columnas para compras
 *
 * @property-read string $id
 * @property-read string $numero_compra
 * @property-read string $subtotal
 * @property-read string $descuento
 * @property-read string $impuestos
 * @property-read string $total
 * @property-read string $estado
 * @property-read string $metodo_pago
 * @property-read ?string $proveedor_id
 * @property-read ?string $usuario_id
 * @property-read ?string $sucursal_id
 * @property-read ?string $notas
 * @property-read \Illuminate\Support\Carbon $fecha_compra
 * @property-read ?\Illuminate\Support\Carbon $created_at
 * @property-read ?\Illuminate\Support\Carbon $updated_at
 * @property-read ?string $caja_id
 * @property-read ?string $tipo_documento
 * @property-read ?string $numero_documento
 * @property-read ?\Illuminate\Support\Carbon $fecha_documento
 * @property-read string $pagado
 * @property-read string $saldo_pendiente
 * @property-read ?string $observaciones
 * @property-read ?string $motivo_cancelacion
 * @property-read ?\Illuminate\Support\Carbon $fecha_cancelacion
 * @property-read ?\Illuminate\Support\Carbon $fecha_vencimiento
 * @property-read ?string $crear_usuario_id
 * @property-read ?string $actualizar_usuario_id
 * @property-read string|null $creador_nombre
 * @property-read string|null $actualizador_nombre
 * @property-read array $auditoria
 */
class Compra extends Model
{
    use ScopedBySucursal;
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
        'pagado',
        'saldo_pendiente',
        'estado',
        'metodo_pago',
        'tipo_documento',
        'numero_documento',
        'fecha_documento',
        'proveedor_id',
        'usuario_id',
        'sucursal_id',
        'caja_id',
        'notas',
        'observaciones',
        'motivo_cancelacion',
        'fecha_cancelacion',
        'fecha_compra',
        'fecha_vencimiento',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'impuestos' => 'decimal:2',
        'total' => 'decimal:2',
        'pagado' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
        'fecha_documento' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_compra' => 'datetime',
        'fecha_cancelacion' => 'datetime',
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
     * Relación con Caja
     */
    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'caja_id');
    }

    /**
     * Relación con productos de la compra
     */
    public function productos(): HasMany
    {
        return $this->hasMany(CompraProducto::class, 'compra_id');
    }

    /**
     * Alias de productos para compatibilidad del recurso.
     */
    public function compraProductos(): HasMany
    {
        return $this->productos();
    }

    /**
     * Relación con los pagos asociados a la compra.
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'documento_id')
            ->where('documento_tipo', 'Compra');
    }

    /**
     * Relación con las notas de crédito emitidas por devoluciones.
     */
    public function notasCredito(): HasMany
    {
        return $this->hasMany(NotaCredito::class, 'documento_id')
            ->where('documento_tipo', 'Compra');
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
        return in_array($this->estado, ['pendiente', 'recibida'], true);
    }

    /**
     * Recibir mercadería (total o parcial) generando lotes y kardex.
     *
     * @param  array<int, array{compra_producto_id: string, cantidad_recibida: int}>  $items
     *
     * @throws ApiException
     */
    public function recibir(array $items = []): void
    {
        if ($this->estado !== 'pendiente') {
            throw ApiException::conflict(
                'La compra no está pendiente de recepción',
                ['estado' => ["La compra está en estado: {$this->estado}"]]
            );
        }

        $itemsPorLinea = [];

        foreach ($items as $item) {
            if (isset($item['compra_producto_id'], $item['cantidad_recibida'])) {
                $itemsPorLinea[$item['compra_producto_id']] = (int) $item['cantidad_recibida'];
            }
        }

        DB::transaction(function () use ($itemsPorLinea) {
            $usuario = auth()->user();

            $totalRecibido = 0;
            $totalSolicitado = 0;

            foreach ($this->productos as $producto) {
                $totalSolicitado += $producto->cantidad;
                $totalRecibido += $producto->cantidad_recibida;

                $pendiente = $producto->cantidad - $producto->cantidad_recibida;

                if ($pendiente <= 0) {
                    continue;
                }

                $cantidadRecibida = isset($itemsPorLinea[$producto->id])
                    ? min($itemsPorLinea[$producto->id], $pendiente)
                    : $pendiente;

                if ($cantidadRecibida <= 0) {
                    continue;
                }

                $lote = $this->resolverOCrearLote($producto, $cantidadRecibida);

                $producto->update([
                    'lote_id' => $lote->id,
                    'cantidad_recibida' => $producto->cantidad_recibida + $cantidadRecibida,
                ]);

                if ($producto->producto) {
                    $producto->producto->increment('stock_actual', $cantidadRecibida);
                }

                MovimientoLote::create([
                    'lote_id' => $lote->id,
                    'tipo_movimiento' => MovimientoLote::ENTRADA_COMPRA,
                    'cantidad' => $cantidadRecibida,
                    'stock_anterior' => max(0, $lote->stock - $cantidadRecibida),
                    'stock_nuevo' => $lote->stock,
                    'documento_tipo' => 'COMPRA',
                    'documento_id' => $this->id,
                    'documento_numero' => $this->numero_compra,
                    'usuario_id' => $usuario?->id,
                    'sucursal_id' => $this->sucursal_id,
                    'producto_nombre' => $producto->producto?->nombre,
                    'producto_codigo' => $producto->producto?->codigo_barras,
                    'costo_unitario' => $producto->precio_unitario,
                    'costo_total' => $producto->precio_unitario * $cantidadRecibida,
                    'observaciones' => "Recepción de compra {$this->numero_compra}",
                ]);

                $totalRecibido += $cantidadRecibida;
            }

            $this->estado = $totalRecibido >= $totalSolicitado ? 'recibida' : 'pendiente';
            $this->save();

            ActivityLog::registrar(
                'recibir_compra',
                'Compra',
                $this->id,
                "Compra {$this->numero_compra} recibida"
            );
        });
    }

    /**
     * Cancelar compra. Si ya se recibió inventario, se revierte.
     *
     * @throws ApiException
     */
    public function cancelar(?string $motivo = null): void
    {
        if (! $this->puedeSerCancelada()) {
            throw ApiException::conflict(
                'La compra no puede ser cancelada',
                ['estado' => ["La compra está en estado: {$this->estado}"]]
            );
        }

        DB::transaction(function () use ($motivo) {
            $this->revertirInventario(MovimientoCaja::ORIGEN_COMPRA);

            $this->update([
                'estado' => 'cancelada',
                'motivo_cancelacion' => $motivo,
                'fecha_cancelacion' => now(),
            ]);

            ActivityLog::registrar(
                'cancelar_compra',
                'Compra',
                $this->id,
                "Compra {$this->numero_compra} cancelada"
            );
        });
    }

    /**
     * Devolver al proveedor una compra recibida, revirtiendo el inventario.
     *
     * @throws ApiException
     */
    public function devolver(?string $motivo = null): void
    {
        if ($this->estado !== 'recibida') {
            throw ApiException::conflict(
                'Solo se pueden devolver compras recibidas',
                ['estado' => ["La compra está en estado: {$this->estado}"]]
            );
        }

        DB::transaction(function () use ($motivo) {
            $this->revertirInventario(MovimientoCaja::ORIGEN_NOTA_CREDITO);

            $updateData = ['estado' => 'devuelta'];

            if ($motivo) {
                $updateData['motivo_cancelacion'] = $motivo;
            }

            $this->update($updateData);

            if ((float) $this->pagado > 0) {
                app(NotaCreditoService::class)->emitir($this, (float) $this->pagado, $motivo);
            }

            ActivityLog::registrar(
                'devolver_compra',
                'Compra',
                $this->id,
                "Compra {$this->numero_compra} devuelta al proveedor"
            );
        });
    }

    /**
     * Revertir el inventario ingresado por esta compra (lotes, stock, kardex)
     * y reintegrar a la caja el dinero pagado al proveedor.
     */
    public function revertirInventario(string $origen = MovimientoCaja::ORIGEN_COMPRA): void
    {
        DB::transaction(function () use ($origen) {
            $usuario = auth()->user();

            foreach ($this->productos()->get() as $producto) {
                if (! $producto->lote_id || (int) $producto->cantidad_recibida <= 0) {
                    continue;
                }

                $lote = Lote::find($producto->lote_id);

                if (! $lote) {
                    continue;
                }

                $cantidad = (int) $producto->cantidad_recibida;
                $stockAnterior = $lote->stock;
                $lote->stock = max(0, $lote->stock - $cantidad);

                if ($lote->stock === 0) {
                    $lote->estado = 'agotado';
                }

                $lote->save();

                MovimientoLote::create([
                    'lote_id' => $lote->id,
                    'tipo_movimiento' => MovimientoLote::SALIDA_DEVOLUCION_PROV,
                    'cantidad' => $cantidad,
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => $lote->stock,
                    'documento_tipo' => 'COMPRA',
                    'documento_id' => $this->id,
                    'documento_numero' => $this->numero_compra,
                    'usuario_id' => $usuario?->id,
                    'sucursal_id' => $this->sucursal_id,
                    'producto_nombre' => $producto->producto?->nombre,
                    'producto_codigo' => $producto->producto?->codigo_barras,
                    'costo_unitario' => $producto->precio_unitario,
                    'costo_total' => $producto->precio_unitario * $cantidad,
                    'observaciones' => "Devolución de la compra {$this->numero_compra}",
                ]);

                if ($producto->producto) {
                    $producto->producto->decrement('stock_actual', $cantidad);
                }

                $producto->update(['cantidad_recibida' => 0]);
            }

            if ((float) $this->pagado > 0 && $this->caja_id) {
                app(CajaLibroService::class)->ingreso(
                    $this->caja_id,
                    (float) $this->pagado,
                    $origen,
                    'Compra',
                    $this->id,
                    $this->numero_compra,
                    "Reintegro de proveedor por compra {$this->numero_compra}"
                );
            }
        });
    }

    /**
     * Resolver el lote a incrementar o crear uno nuevo a partir de la línea de compra.
     */
    private function resolverOCrearLote(CompraProducto $producto, int $cantidadRecibida): Lote
    {
        $lote = $producto->lote_id ? Lote::find($producto->lote_id) : null;

        if ($lote) {
            $stockAnterior = $lote->stock;
            $costoPromedio = $lote->precio_costo_promedio !== null
                ? (float) $lote->precio_costo_promedio
                : (float) ($lote->precio_costo ?? $producto->precio_unitario);

            $lote->stock += $cantidadRecibida;
            $lote->precio_costo = $producto->precio_unitario;
            $lote->precio_costo_promedio = $stockAnterior > 0
                ? round((($stockAnterior * $costoPromedio) + ($cantidadRecibida * $producto->precio_unitario)) / $lote->stock, 2)
                : $producto->precio_unitario;

            if (! in_array($lote->estado, ['disponible', 'parcial'], true)) {
                $lote->estado = 'disponible';
            }

            $lote->save();

            return $lote;
        }

        return Lote::create([
            'producto_id' => $producto->producto_id,
            'proveedor_id' => $this->proveedor_id,
            'compra_id' => $this->id,
            'numero_lote' => $producto->numero_lote
                ?: 'LOTE-'.strtoupper(substr($this->numero_compra, 2)).'-'.str_pad((string) $producto->cantidad, 4, '0', STR_PAD_LEFT),
            'fecha_vencimiento' => $producto->fecha_vencimiento ?: now()->addYear(),
            'fecha_ingreso' => now(),
            'stock' => $cantidadRecibida,
            'stock_comprometido' => 0,
            'stock_minimo' => 0,
            'precio_costo' => $producto->precio_unitario,
            'precio_costo_promedio' => $producto->precio_unitario,
            'estado' => 'disponible',
        ]);
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
            ActivityLog::registrar(
                'crear_compra',
                'Compra',
                $compra->id,
                "Compra {$compra->numero_compra} creada"
            );
        });

        static::updated(function ($compra) {
            ActivityLog::registrar(
                'actualizar_compra',
                'Compra',
                $compra->id,
                "Compra {$compra->numero_compra} actualizada"
            );
        });

        static::deleted(function ($compra) {
            ActivityLog::registrar(
                'eliminar_compra',
                'Compra',
                $compra->id,
                "Compra {$compra->numero_compra} eliminada"
            );
        });
    }
}