<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\ActivityLog;
use App\Models\Compra;
use App\Models\CompraProducto;
use App\Models\Lote;
use App\Models\MovimientoLote;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraProducto;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

class OrdenCompraService
{
    /**
     * Crear una solicitud u orden de compra con sus líneas.
     *
     * @param  array<string, mixed>  $data
     */
    public function crear(array $data): OrdenCompra
    {
        $validated = validator($data, [
            'tipo' => ['required', 'in:solicitud,orden'],
            'prioridad' => ['nullable', 'in:baja,normal,alta,urgente'],
            'proveedor_id' => ['nullable', 'exists:proveedores,id'],
            'fecha_solicitud' => ['nullable', 'date'],
            'fecha_estimada' => ['nullable', 'date'],
            'notas' => ['nullable', 'string', 'max:1000'],
            'productos' => ['required', 'array', 'min:1'],
            'productos.*.producto_id' => ['required', 'exists:productos,id'],
            'productos.*.cantidad' => ['required', 'numeric', 'gt:0'],
            'productos.*.precio_unitario' => ['nullable', 'numeric', 'min:0'],
            'productos.*.descuento' => ['nullable', 'numeric', 'min:0'],
            'productos.*.impuesto' => ['nullable', 'numeric', 'min:0'],
            'productos.*.fecha_vencimiento' => ['nullable', 'date'],
        ])->validate();

        return DB::transaction(function () use ($validated) {
            $usuario = auth()->user();

            $orden = OrdenCompra::create([
                'numero_orden' => OrdenCompra::generateNumeroOrden(),
                'tipo' => $validated['tipo'],
                'prioridad' => $validated['prioridad'] ?? OrdenCompra::PRIORIDAD_NORMAL,
                'estado' => $validated['tipo'] === OrdenCompra::TIPO_ORDEN
                    ? OrdenCompra::ESTADO_APROBADA
                    : OrdenCompra::ESTADO_PENDIENTE_APROBACION,
                'proveedor_id' => $validated['proveedor_id'] ?? null,
                'sucursal_id' => $usuario?->sucursal_id,
                'usuario_id' => $usuario?->id,
                'fecha_solicitud' => $validated['fecha_solicitud'] ?? now()->toDateString(),
                'fecha_estimada' => $validated['fecha_estimada'] ?? null,
                'notas' => $validated['notas'] ?? null,
            ]);

            foreach ($validated['productos'] as $producto) {
                $precio = (float) ($producto['precio_unitario'] ?? 0);
                $descuento = (float) ($producto['descuento'] ?? 0);
                $impuesto = (float) ($producto['impuesto'] ?? 0);
                $cantidad = (float) $producto['cantidad'];

                OrdenCompraProducto::create([
                    'orden_id' => $orden->id,
                    'producto_id' => $producto['producto_id'],
                    'cantidad' => $cantidad,
                    'cantidad_recibida' => 0,
                    'precio_unitario' => $precio,
                    'descuento' => $descuento,
                    'impuesto' => $impuesto,
                    'subtotal' => round($cantidad * $precio, 2),
                    'fecha_vencimiento' => $producto['fecha_vencimiento'] ?? null,
                ]);
            }

            $orden->recalcularTotales();

            ActivityLog::registrar(
                'crear_orden_compra',
                'OrdenCompra',
                $orden->id,
                "Orden de compra {$orden->numero_orden} creada"
            );

            return $orden->load(['proveedor', 'productos.producto']);
        }, 3);
    }

    /**
     * Aprobar una solicitud u orden de compra.
     */
    public function aprobar(OrdenCompra $orden, string $usuarioId, ?string $notas = null): OrdenCompra
    {
        $orden->verificarTransicion(
            OrdenCompra::ESTADO_PENDIENTE_APROBACION,
            'Solo las solicitudes pendientes de aprobación pueden aprobarse'
        );

        $update = [
            'estado' => OrdenCompra::ESTADO_APROBADA,
            'aprobado_por_id' => $usuarioId,
            'fecha_aprobacion' => now(),
        ];

        if ($notas) {
            $update['notas'] = trim($orden->notas === null ? '' : $orden->notas.' | ').$notas;
        }

        $orden->update($update);

        ActivityLog::registrar(
            'aprobar_orden_compra',
            'OrdenCompra',
            $orden->id,
            "Orden de compra {$orden->numero_orden} aprobada"
        );

        return $orden->fresh()->load(['proveedor', 'productos.producto']);
    }

    /**
     * Rechazar una solicitud de compra.
     */
    public function rechazar(OrdenCompra $orden, string $usuarioId, string $motivo): OrdenCompra
    {
        $orden->verificarTransicion(
            OrdenCompra::ESTADO_PENDIENTE_APROBACION,
            'Solo las solicitudes pendientes de aprobación pueden rechazarse'
        );

        $orden->update([
            'estado' => OrdenCompra::ESTADO_RECHAZADA,
            'aprobado_por_id' => $usuarioId,
            'fecha_aprobacion' => now(),
            'motivo_rechazo' => $motivo,
        ]);

        ActivityLog::registrar(
            'rechazar_orden_compra',
            'OrdenCompra',
            $orden->id,
            "Solicitud {$orden->numero_orden} rechazada"
        );

        return $orden->fresh()->load(['proveedor', 'productos.producto']);
    }

    /**
     * Marcar la orden como enviada al proveedor.
     */
    public function enviar(OrdenCompra $orden, string $usuarioId): OrdenCompra
    {
        $orden->verificarTransicion(
            OrdenCompra::ESTADO_APROBADA,
            'Solo las órdenes aprobadas pueden enviarse'
        );

        $orden->update([
            'estado' => OrdenCompra::ESTADO_ENVIADA,
            'fecha_envio' => now(),
        ]);

        ActivityLog::registrar(
            'enviar_orden_compra',
            'OrdenCompra',
            $orden->id,
            "Orden de compra {$orden->numero_orden} enviada al proveedor"
        );

        return $orden->fresh()->load(['proveedor', 'productos.producto']);
    }

    /**
     * Recibir mercadería (total o parcial) de una orden enviada.
     * Genera una Compra con sus lotes y kardex sin duplicar captura.
     *
     * @param  array<int, array{orden_producto_id: string, cantidad: int}>  $items
     */
    public function recibir(OrdenCompra $orden, array $items, string $usuarioId): OrdenCompra
    {
        $orden->verificarTransicion(
            OrdenCompra::ESTADO_ENVIADA,
            'Solo las órdenes enviadas al proveedor pueden recibirse'
        );

        $itemsPorLinea = collect($items)->mapWithKeys(
            fn ($item) => [$item['orden_producto_id'] => (int) $item['cantidad']]
        )->all();

        return DB::transaction(function () use ($orden, $itemsPorLinea, $usuarioId) {
            $usuario = Usuario::find($usuarioId);

            // Reutilizar la Compra ya generada en una recepción anterior (recepción parcial).
            $compra = Compra::where('numero_compra', $orden->numero_orden)->first();

            if ($compra === null) {
                $compra = Compra::create([
                    'numero_compra' => $orden->numero_orden,
                    'proveedor_id' => $orden->proveedor_id,
                    'usuario_id' => $usuarioId,
                    'sucursal_id' => $orden->sucursal_id,
                    'metodo_pago' => 'efectivo',
                    'subtotal' => 0,
                    'descuento' => (float) $orden->descuento,
                    'impuestos' => (float) $orden->impuestos,
                    'total' => 0,
                    'pagado' => 0,
                    'saldo_pendiente' => 0,
                    'estado' => 'pendiente',
                    'fecha_compra' => now(),
                    'notas' => 'Generada desde orden de compra '.$orden->numero_orden,
                ]);
            }

            foreach ($orden->productos()->lockForUpdate()->get() as $linea) {
                $pendiente = (float) $linea->cantidad - (float) $linea->cantidad_recibida;
                $cantidadRecibida = isset($itemsPorLinea[$linea->id])
                    ? min((int) $itemsPorLinea[$linea->id], (int) $pendiente)
                    : (int) $pendiente;

                if ($cantidadRecibida <= 0) {
                    continue;
                }

                // Línea en la compra.
                $secuenciaLote = Lote::where('producto_id', $linea->producto_id)
                    ->where('numero_lote', 'like', 'OC-'.$orden->numero_orden.'-%')
                    ->count();
                $numeroLote = 'OC-'.$orden->numero_orden.'-'.str_pad((string) ($secuenciaLote + 1), 3, '0', STR_PAD_LEFT);

                $lineaCompra = CompraProducto::create([
                    'compra_id' => $compra->id,
                    'producto_id' => $linea->producto_id,
                    'numero_lote' => $numeroLote,
                    'fecha_vencimiento' => $linea->fecha_vencimiento ?? now()->addYear(),
                    'cantidad' => $cantidadRecibida,
                    'cantidad_recibida' => $cantidadRecibida,
                    'precio_unitario' => (float) $linea->precio_unitario,
                    'descuento_unitario' => (float) $linea->descuento,
                ]);

                // Generar lote y kardex de entrada.
                $lote = Lote::create([
                    'producto_id' => $linea->producto_id,
                    'proveedor_id' => $orden->proveedor_id,
                    'compra_id' => $compra->id,
                    'numero_lote' => $numeroLote,
                    'fecha_vencimiento' => $linea->fecha_vencimiento ?? now()->addYear(),
                    'fecha_ingreso' => now(),
                    'stock' => $cantidadRecibida,
                    'stock_comprometido' => 0,
                    'stock_minimo' => $linea->producto->stock_minimo ?? 0,
                    'precio_costo' => (float) $linea->precio_unitario,
                    'precio_costo_promedio' => (float) $linea->precio_unitario,
                    'estado' => 'disponible',
                ]);

                MovimientoLote::create([
                    'lote_id' => $lote->id,
                    'tipo_movimiento' => MovimientoLote::ENTRADA_COMPRA,
                    'cantidad' => $cantidadRecibida,
                    'stock_anterior' => 0,
                    'stock_nuevo' => $cantidadRecibida,
                    'documento_tipo' => 'Compra',
                    'documento_id' => $compra->id,
                    'documento_numero' => $compra->numero_compra,
                    'usuario_id' => $usuarioId,
                    'sucursal_id' => $orden->sucursal_id,
                    'producto_nombre' => $linea->producto?->nombre,
                    'producto_codigo' => $linea->producto?->codigo_barras,
                    'costo_unitario' => (float) $linea->precio_unitario,
                    'costo_total' => round($cantidadRecibida * (float) $linea->precio_unitario, 2),
                    'observaciones' => "Recepción de orden {$orden->numero_orden}",
                ]);

                $linea->producto?->increment('stock_actual', $cantidadRecibida);

                $linea->update([
                    'cantidad_recibida' => (float) $linea->cantidad_recibida + $cantidadRecibida,
                    'lote_id' => $lote->id,
                ]);
            }

            $compra->calcularTotales();

            if ($orden->estaRecibidaTotalmente()) {
                $orden->update([
                    'estado' => OrdenCompra::ESTADO_RECIBIDA,
                    'recibido_por_id' => $usuarioId,
                    'fecha_recepcion' => now()->toDateString(),
                    'fecha_finalizacion' => now(),
                ]);
            } else {
                $orden->update([
                    'estado' => OrdenCompra::ESTADO_ENVIADA,
                    'recibido_por_id' => $usuarioId,
                    'fecha_recepcion' => now()->toDateString(),
                ]);
            }

            ActivityLog::registrar(
                'recibir_orden_compra',
                'OrdenCompra',
                $orden->id,
                "Orden de compra {$orden->numero_orden} recibida"
            );

            return $orden->fresh(['proveedor', 'productos.producto', 'productos.lote']);
        }, 3);
    }

    /**
     * Cancelar una orden que aún no se recibió.
     */
    public function cancelar(OrdenCompra $orden, string $usuarioId, ?string $motivo = null): OrdenCompra
    {
        if (in_array($orden->estado, [OrdenCompra::ESTADO_RECIBIDA, OrdenCompra::ESTADO_CANCELADA], true)) {
            throw ApiException::conflict(
                'La orden no puede cancelarse en su estado actual',
                ['estado' => ["La orden está en estado: {$orden->estado}"]]
            );
        }

        $orden->update([
            'estado' => OrdenCompra::ESTADO_CANCELADA,
            'motivo_rechazo' => $motivo ?? $orden->motivo_rechazo,
            'fecha_finalizacion' => now(),
        ]);

        ActivityLog::registrar(
            'cancelar_orden_compra',
            'OrdenCompra',
            $orden->id,
            "Orden de compra {$orden->numero_orden} cancelada"
        );

        return $orden->fresh(['proveedor', 'productos.producto']);
    }

    /**
     * Sugerencias de reposición según stock mínimo, stock actual y días de
     * rotación por producto. Son solo recomendaciones; la decisión final
     * la toma el responsable.
     *
     * @return array<int, array<string, mixed>>
     */
    public function sugerirReposicion(?string $sucursalId = null): array
    {
        $productos = Producto::query()
            ->where('estado', 'activo')
            ->get();

        return $productos->map(function (Producto $producto) {
            // Stock disponible sumando lotes vigentes no comprometidos.
            $stockDisponible = max(0, $producto->stock_actual);

            // Rotación aproximada: unidades vendidas en los últimos 30 días.
            $ventas30 = $producto->ventaProductos()
                ->where('created_at', '>=', now()->subDays(30))
                ->sum('cantidad');

            $stockMinimo = (int) ($producto->stock_minimo ?? 0);
            $stockMaximo = (int) ($producto->stock_maximo ?? 0);

            if ($stockDisponible > $stockMinimo && $ventas30 <= 0) {
                return null;
            }

            // Proyección de consumo diario y días de cobertura actual.
            $consumoDiario = max(0, $ventas30 / 30);
            $diasCobertura = $consumoDiario > 0
                ? (int) floor($stockDisponible / $consumoDiario)
                : ($stockDisponible > 0 ? 365 : 0);

            // Cantidad sugerida para llegar al máximo, o cubrir 30 días de consumo.
            $objetivo = max($stockMinimo, (int) ceil($consumoDiario * 30));
            if ($stockMaximo > 0) {
                $objetivo = max($objetivo, $stockMaximo);
            }

            $cantidadSugerida = max(0, $objetivo - $stockDisponible);

            if ($cantidadSugerida <= 0) {
                return null;
            }

            return [
                'producto_id' => $producto->id,
                'producto' => $producto->nombre,
                'codigo' => $producto->codigo_barras,
                'stock_actual' => $stockDisponible,
                'stock_minimo' => $stockMinimo,
                'stock_maximo' => $stockMaximo,
                'ventas_30_dias' => $ventas30,
                'dias_cobertura' => $diasCobertura,
                'cantidad_sugerida' => $cantidadSugerida,
            ];
        })->filter()
            ->sortByDesc('ventas_30_dias')
            ->values()
            ->all();
    }
}