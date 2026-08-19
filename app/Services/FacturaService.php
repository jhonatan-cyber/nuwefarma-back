<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\FiscalProvider;
use App\Exceptions\ApiException;
use App\Models\ActivityLog;
use App\Models\Empresa;
use App\Models\Factura;
use App\Models\FacturaDetalle;
use App\Models\PuntoVenta;
use App\Models\SiatSesion;
use App\Models\SiatTransaccion;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FacturaService
{
    public function __construct(private FiscalProvider $provider) {}

    /**
     * Obtener (o renovar) el CUIS vigente del punto de venta.
     */
    public function solicitarCuis(PuntoVenta $puntoVenta, ?string $usuarioId): SiatSesion
    {
        $vigente = SiatSesion::vigente($puntoVenta->id, SiatSesion::TIPO_CUIS);

        if ($vigente) {
            return $vigente;
        }

        $respuesta = $this->provider->solicitarCuis($puntoVenta);

        $sesion = SiatSesion::create([
            'punto_venta_id' => $puntoVenta->id,
            'tipo' => SiatSesion::TIPO_CUIS,
            'codigo' => $respuesta['codigo'],
            'codigo_control' => $respuesta['codigo_control'] ?? null,
            'fecha_vigencia' => $respuesta['fecha_vigencia'] ?? now()->toDateString(),
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addYear(),
            'estado' => SiatSesion::ESTADO_ACTIVA,
        ]);

        $this->transaccion(
            SiatTransaccion::OP_SOLICITAR_CUIS,
            $puntoVenta,
            null,
            ['punto_venta' => $puntoVenta->codigo_poa],
            $respuesta
        );

        return $sesion;
    }

    /**
     * Obtener (o renovar) el CUFD vigente del punto de venta.
     */
    public function solicitarCufd(PuntoVenta $puntoVenta, ?string $usuarioId): SiatSesion
    {
        $vigente = SiatSesion::vigente($puntoVenta->id, SiatSesion::TIPO_CUFD);

        if ($vigente) {
            return $vigente;
        }

        $respuesta = $this->provider->solicitarCufd($puntoVenta);

        // Las sesiones CUFD anteriores pasan a inactivas (se renuevan a diario).
        SiatSesion::where('punto_venta_id', $puntoVenta->id)
            ->where('tipo', SiatSesion::TIPO_CUFD)
            ->update(['estado' => SiatSesion::ESTADO_INACTIVA]);

        $sesion = SiatSesion::create([
            'punto_venta_id' => $puntoVenta->id,
            'tipo' => SiatSesion::TIPO_CUFD,
            'codigo' => $respuesta['codigo'],
            'codigo_control' => $respuesta['codigo_control'] ?? null,
            'fecha_vigencia' => $respuesta['fecha_vigencia'] ?? now()->toDateString(),
            'fecha_inicio' => now(),
            'fecha_fin' => now()->endOfDay(),
            'estado' => SiatSesion::ESTADO_ACTIVA,
        ]);

        $this->transaccion(
            SiatTransaccion::OP_SOLICITAR_CUFD,
            $puntoVenta,
            null,
            ['punto_venta' => $puntoVenta->codigo_poa],
            $respuesta
        );

        return $sesion;
    }

    /**
     * Emitir factura desde una venta completada; es idempotente por venta.
     */
    public function emitirDesdeVenta(Venta $venta, PuntoVenta $puntoVenta, string $usuarioId): Factura
    {
        $existente = Factura::where('venta_id', $venta->id)->first();

        if ($existente && $existente->estado !== Factura::ESTADO_ANULADA) {
            throw ApiException::conflict(
                'La venta ya tiene una factura emitida',
                ['venta_id' => ['Esta venta ya fue facturada']]
            );
        }

        if ($venta->estado !== 'completada') {
            throw ApiException::conflict(
                'Solo las ventas completadas pueden facturarse',
                ['venta_id' => ["La venta está en estado: {$venta->estado}"]]
            );
        }

        $empresa = Empresa::obtenerODefault();
        $cuis = $this->solicitarCuis($puntoVenta, $usuarioId)->codigo;
        $cufd = $this->solicitarCufd($puntoVenta, $usuarioId)->codigo;

        $numero = str_pad((string) (Factura::siguienteNumero($puntoVenta->id) + 1), 5, '0', STR_PAD_LEFT);
        $cliente = $venta->cliente;
        $nitCliente = $cliente?->nit ?: $cliente?->ci ?: '0';

        $datos = [
            'nit' => $empresa->nit,
            'razon_social' => $empresa->razon_social,
            'numero_factura' => $numero,
            'codigo_poa' => $puntoVenta->codigo_poa,
            'tipo_emision' => Factura::TIPO_EMISION_ONLINE,
            'tipo_factura' => 1,
            'nit_cliente' => $nitCliente,
            'fecha_emision' => now()->format('YmdHis').sprintf('%03d', (int) (now()->microsecond / 1000)),
            'cufd' => $cufd,
            'cuis' => $cuis,
            'monto_total' => round((float) $venta->total, 2),
            'cliente' => $cliente ? trim(($cliente->nombre ?? '').' '.($cliente->apellidos ?? '')) : 'Consumidor Final',
        ];

        $respuesta = $this->provider->emitir($datos);

        return DB::transaction(function () use ($venta, $puntoVenta, $usuarioId, $numero, $nitCliente, $cufd, $cuis, $respuesta, $empresa, $cliente) {
            $existeAnulada = Factura::where('venta_id', $venta->id)
                ->where('estado', Factura::ESTADO_ANULADA)
                ->first();

            $factura = Factura::create([
                'venta_id' => $venta->id,
                'punto_venta_id' => $puntoVenta->id,
                'sucursal_id' => $venta->sucursal_id,
                'usuario_id' => $usuarioId,
                'numero_factura' => $numero,
                'cuf' => $respuesta['cuf'],
                'cufd' => $cufd,
                'cuis' => $cuis,
                'numero_autorizacion' => $respuesta['numero_autorizacion'] ?? null,
                'codigo_control' => $respuesta['codigo_control'] ?? null,
                'tipo_emision' => Factura::TIPO_EMISION_ONLINE,
                'tipo_documento_sector' => Factura::TIPO_DOC_CON_CREDITO,
                'tipo_pago' => $venta->metodo_pago ?? 'otro',
                'nit_cliente' => $nitCliente,
                'razon_social_cliente' => $cliente
                    ? (($cliente->razon_social ?? '') ?: trim(($cliente->nombre ?? '').' '.($cliente->apellidos ?? '')))
                    : 'Consumidor Final',
                'codigo_cliente' => $cliente?->id,
                'fecha_emision' => now()->toDateString(),
                'leyenda' => $respuesta['leyenda'] ?? config('siat.leyenda'),
                'subtotal' => round((float) $venta->subtotal, 2),
                'descuento' => round((float) $venta->descuento, 2),
                'monto_total' => round((float) $venta->total, 2),
                'monto_sujeto_iva' => round((float) $venta->total, 2),
                'monto_no_sujeto' => 0,
                'monto_ice' => 0,
                'estado' => Factura::ESTADO_EMITIDA,
                'qr' => $respuesta['qr'] ?? null,
            ]);

            foreach ($venta->ventaProductos()->with('producto')->get() as $item) {
                FacturaDetalle::create([
                    'factura_id' => $factura->id,
                    'producto_id' => $item->producto_id,
                    'codigo_producto' => $item->producto?->codigo_barras,
                    'descripcion' => $item->producto?->nombre ?? 'Producto',
                    'cantidad' => (int) $item->cantidad,
                    'unidad_medida' => 'UNIDAD',
                    'precio_unitario' => round((float) $item->precio_unitario, 2),
                    'descuento_unitario' => round((float) $item->descuento_unitario, 2),
                    'subtotal' => round((float) $item->subtotal, 2),
                    'monto_descuento' => round((float) $item->descuento_unitario * (int) $item->cantidad, 2),
                ]);
            }

            ActivityLog::registrar(
                'emitir_factura',
                'Factura',
                $factura->id,
                "Factura {$numero} emitida para venta {$venta->numero_venta}"
            );

            $this->transaccion(
                SiatTransaccion::OP_EMITIR,
                $puntoVenta,
                $factura,
                ['venta_id' => $venta->id, 'numero_factura' => $numero],
                $respuesta
            );

            return $factura->fresh(['venta', 'puntoVenta', 'sucursal', 'usuario', 'detalles.producto']);
        }, 3);
    }

    /**
     * Emitir factura en contingencia (sin conexión al SIAT).
     */
    public function emitirEnContingencia(Venta $venta, PuntoVenta $puntoVenta, string $usuarioId, string $motivo): Factura
    {
        $cuis = $this->solicitarCuis($puntoVenta, $usuarioId)->codigo;
        $cufd = $this->solicitarCufd($puntoVenta, $usuarioId)->codigo;

        $empresa = Empresa::obtenerODefault();
        $numero = str_pad((string) (Factura::siguienteNumero($puntoVenta->id) + 1), 5, '0', STR_PAD_LEFT);
        $cliente = $venta->cliente;
        $nitCliente = $cliente?->nit ?: $cliente?->ci ?: '0';

        $datos = [
            'nit' => $empresa->nit,
            'razon_social' => $empresa->razon_social,
            'numero_factura' => $numero,
            'codigo_poa' => $puntoVenta->codigo_poa,
            'tipo_emision' => Factura::TIPO_EMISION_CONTINGENCIA,
            'tipo_factura' => 1,
            'nit_cliente' => $nitCliente,
            'fecha_emision' => now()->format('YmdHis').sprintf('%03d', (int) (now()->microsecond / 1000)),
            'cufd' => $cufd,
            'cuis' => $cuis,
            'monto_total' => round((float) $venta->total, 2),
        ];

        $respuesta = $this->provider->emitir($datos);

        return DB::transaction(function () use ($venta, $puntoVenta, $usuarioId, $numero, $nitCliente, $cufd, $cuis, $respuesta, $empresa, $cliente, $motivo) {
            $factura = Factura::create([
                'venta_id' => $venta->id,
                'punto_venta_id' => $puntoVenta->id,
                'sucursal_id' => $venta->sucursal_id,
                'usuario_id' => $usuarioId,
                'numero_factura' => $numero,
                'cuf' => $respuesta['cuf'],
                'cufd' => $cufd,
                'cuis' => $cuis,
                'numero_autorizacion' => $respuesta['numero_autorizacion'] ?? null,
                'codigo_control' => $respuesta['codigo_control'] ?? null,
                'tipo_emision' => Factura::TIPO_EMISION_CONTINGENCIA,
                'tipo_documento_sector' => Factura::TIPO_DOC_CON_CREDITO,
                'tipo_pago' => $venta->metodo_pago ?? 'otro',
                'nit_cliente' => $nitCliente,
                'razon_social_cliente' => $cliente
                    ? (($cliente->razon_social ?? '') ?: trim(($cliente->nombre ?? '').' '.($cliente->apellidos ?? '')))
                    : 'Consumidor Final',
                'codigo_cliente' => $cliente?->id,
                'fecha_emision' => now()->toDateString(),
                'leyenda' => $respuesta['leyenda'] ?? config('siat.leyenda'),
                'subtotal' => round((float) $venta->subtotal, 2),
                'descuento' => round((float) $venta->descuento, 2),
                'monto_total' => round((float) $venta->total, 2),
                'monto_sujeto_iva' => 0,
                'monto_no_sujeto' => round((float) $venta->total, 2),
                'monto_ice' => 0,
                'estado' => Factura::ESTADO_EMITIDA,
                'qr' => $respuesta['qr'] ?? null,
                'respuesta_siat' => json_encode(['motivo' => $motivo], JSON_UNESCAPED_UNICODE),
            ]);

            $this->transaccion(
                SiatTransaccion::OP_EMITIR,
                $puntoVenta,
                $factura,
                ['venta_id' => $venta->id, 'contingencia' => true, 'motivo' => $motivo],
                $respuesta
            );

            return $factura->fresh(['venta', 'puntoVenta', 'sucursal', 'usuario']);
        }, 3);
    }

    /**
     * Anular una factura emitida.
     */
    public function anular(Factura $factura, string $usuarioId, string $codigoMotivo, string $motivoAnulacion): Factura
    {
        if ($factura->estado !== Factura::ESTADO_EMITIDA) {
            throw ApiException::conflict(
                'Solo las facturas emitidas pueden anularse',
                ['estado' => ["La factura está en estado: {$factura->estado}"]]
            );
        }

        $puntoVenta = $factura->puntoVenta;
        $respuesta = $this->provider->anular(
            $puntoVenta,
            $factura->cuf,
            $codigoMotivo,
            $motivoAnulacion
        );

        $factura->update([
            'estado' => Factura::ESTADO_ANULADA,
            'motivo_anulacion' => $motivoAnulacion,
            'fecha_anulacion' => now(),
        ]);

        ActivityLog::registrar(
            'anular_factura',
            'Factura',
            $factura->id,
            "Factura {$factura->numero_factura} anulada ({$codigoMotivo})"
        );

        $this->transaccion(
            SiatTransaccion::OP_ANULAR,
            $puntoVenta,
            $factura,
            ['cuf' => $factura->cuf, 'codigo_motivo' => $codigoMotivo],
            $respuesta
        );

        return $factura->fresh(['venta', 'puntoVenta', 'sucursal', 'usuario']);
    }

    /**
     * Consultar el estado de una factura ante el provider.
     */
    public function consultar(Factura $factura): array
    {
        $respuesta = $this->provider->consultar($factura->cuf);

        $this->transaccion(
            SiatTransaccion::OP_CONSULTAR,
            $factura->puntoVenta,
            $factura,
            ['cuf' => $factura->cuf],
            $respuesta
        );

        return $respuesta;
    }

    /**
     * Registrar una transacción idempotente del provider.
     *
     * @param  array<string, mixed>  $request
     * @param  array<string, mixed>  $respuesta
     */
    private function transaccion(
        string $tipoOperacion,
        ?PuntoVenta $puntoVenta,
        ?Factura $factura,
        array $request,
        array $respuesta,
    ): void {
        SiatTransaccion::create([
            'uuid_request' => Str::uuid()->toString(),
            'tipo_operacion' => $tipoOperacion,
            'factura_id' => $factura?->id,
            'punto_venta_id' => $puntoVenta?->id,
            'cuf' => $factura?->cuf,
            'estado' => SiatTransaccion::ESTADO_EXITO,
            'codigo_respuesta' => $respuesta['codigo_respuesta'] ?? '0',
            'descripcion' => $respuesta['descripcion'] ?? null,
            'request_payload' => $request,
            'response_payload' => $respuesta,
        ]);
    }
}