<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Factura;
use App\Models\PuntoVenta;
use App\Models\SiatTransaccion;
use App\Models\Venta;
use App\Services\FacturaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FacturaController extends Controller
{
    public function __construct(private FacturaService $facturaService) {}

    /**
     * Listar facturas con filtros.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Factura::with(['venta', 'puntoVenta', 'sucursal', 'usuario']);

        if ($request->estado) {
            $query->where('estado', $request->estado);
        }

        if ($request->punto_venta_id) {
            $query->where('punto_venta_id', $request->punto_venta_id);
        }

        if ($request->tipo_emision) {
            $query->where('tipo_emision', $request->tipo_emision);
        }

        if ($request->fecha_desde) {
            $query->whereDate('fecha_emision', '>=', $request->fecha_desde);
        }

        if ($request->fecha_hasta) {
            $query->whereDate('fecha_emision', '<=', $request->fecha_hasta);
        }

        if ($request->q) {
            $query->where(function ($q) use ($request) {
                $q->where('numero_factura', 'like', "%{$request->q}%")
                    ->orWhere('cuf', 'like', "%{$request->q}%")
                    ->orWhere('razon_social_cliente', 'like', "%{$request->q}%");
            });
        }

        if ($request->sucursal_id) {
            $query->where('sucursal_id', $request->sucursal_id);
        } elseif ($request->user()->sucursal_id) {
            $query->where('sucursal_id', $request->user()->sucursal_id);
        }

        $query->orderByDesc('created_at');
        $perPage = min($request->per_page ?? 15, 100);

        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage)->through(fn ($f) => $this->formatear($f, true)),
        ], Response::HTTP_OK);
    }

    /**
     * Mostrar una factura con sus detalles.
     */
    public function show(Request $request, Factura $factura): JsonResponse
    {
        $factura->load(['venta', 'puntoVenta', 'sucursal', 'usuario', 'detalles.producto']);

        return response()->json([
            'success' => true,
            'data' => $this->formatear($factura, false),
        ], Response::HTTP_OK);
    }

    /**
     * Datos de la empresa fiscal activa.
     */
    public function empresa(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Empresa::obtenerODefault(),
        ], Response::HTTP_OK);
    }

    /**
     * Guardar/actualizar la empresa fiscal.
     */
    public function guardarEmpresa(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nit' => ['required', 'string', 'max:30'],
            'razon_social' => ['required', 'string', 'max:250'],
            'nombre_comercial' => ['nullable', 'string', 'max:250'],
            'codigo_actividad' => ['required', 'string', 'max:20'],
            'descripcion_actividad' => ['nullable', 'string', 'max:500'],
            'municipio' => ['nullable', 'string', 'max:100'],
            'departamento' => ['nullable', 'string', 'max:60'],
            'direccion' => ['nullable', 'string', 'max:250'],
            'telefono' => ['nullable', 'string', 'max:40'],
            'correo_electronico' => ['nullable', 'email', 'max:200'],
            'regimen' => ['nullable', 'in:general,simplificado,integrado'],
        ]);

        $empresa = Empresa::obtenerODefault();
        $empresa->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Empresa fiscal actualizada',
            'data' => $empresa->fresh(),
        ], Response::HTTP_OK);
    }

    /**
     * Emitir factura de una venta completada.
     */
    public function emitir(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'venta_id' => ['required', 'exists:ventas,id'],
            'punto_venta_id' => ['required', 'exists:puntos_venta,id'],
        ]);

        $venta = Venta::findOrFail($validated['venta_id']);
        $puntoVenta = PuntoVenta::findOrFail($validated['punto_venta_id']);

        $factura = $this->facturaService->emitirDesdeVenta($venta, $puntoVenta, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => "Factura {$factura->numero_factura} emitida",
            'data' => $this->formatear($factura, false),
        ], Response::HTTP_CREATED);
    }

    /**
     * Emitir factura en contingencia.
     */
    public function emitirContingencia(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'venta_id' => ['required', 'exists:ventas,id'],
            'punto_venta_id' => ['required', 'exists:puntos_venta,id'],
            'motivo' => ['nullable', 'string', 'max:500'],
        ]);

        $venta = Venta::findOrFail($validated['venta_id']);
        $puntoVenta = PuntoVenta::findOrFail($validated['punto_venta_id']);

        $factura = $this->facturaService->emitirEnContingencia(
            $venta,
            $puntoVenta,
            $request->user()->id,
            $validated['motivo'] ?? 'Sin conexión al SIAT'
        );

        return response()->json([
            'success' => true,
            'message' => "Factura {$factura->numero_factura} emitida en contingencia",
            'data' => $this->formatear($factura, false),
        ], Response::HTTP_CREATED);
    }

    /**
     * Anular una factura emitida.
     */
    public function anular(Request $request, Factura $factura): JsonResponse
    {
        $validated = $request->validate([
            'codigo_motivo' => ['required', 'string', 'max:10'],
            'motivo_anulacion' => ['required', 'string', 'max:500'],
        ]);

        $factura = $this->facturaService->anular(
            $factura,
            $request->user()->id,
            $validated['codigo_motivo'],
            $validated['motivo_anulacion']
        );

        return response()->json([
            'success' => true,
            'message' => 'Factura anulada',
            'data' => $this->formatear($factura, false),
        ], Response::HTTP_OK);
    }

    /**
     * Consultar estado de una factura ante el SIAT.
     */
    public function consultar(Request $request, Factura $factura): JsonResponse
    {
        $respuesta = $this->facturaService->consultar($factura);

        return response()->json([
            'success' => true,
            'data' => [
                'factura_id' => $factura->id,
                'cuf' => $factura->cuf,
                'estado' => $respuesta['estado'] ?? $factura->estado,
                'codigo_respuesta' => $respuesta['codigo_respuesta'] ?? '0',
                'descripcion' => $respuesta['descripcion'] ?? null,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Crear un punto de venta.
     */
    public function crearPuntoVenta(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sucursal_id' => ['nullable', 'exists:sucursals,id'],
            'nombre' => ['nullable', 'string', 'max:200'],
            'telefono' => ['nullable', 'string', 'max:40'],
            'direccion' => ['nullable', 'string', 'max:250'],
            'tipo' => ['nullable', 'in:fisica,web'],
            'ambiente' => ['nullable', 'in:pruebas,produccion'],
        ]);

        $puntoVenta = PuntoVenta::create([
            'sucursal_id' => $validated['sucursal_id'] ?? $request->user()->sucursal_id,
            'codigo_poa' => $validated['codigo_poa'] ?? PuntoVenta::generarCodigoPoa(),
            'nombre' => $validated['nombre'] ?? null,
            'telefono' => $validated['telefono'] ?? null,
            'direccion' => $validated['direccion'] ?? null,
            'tipo' => $validated['tipo'] ?? 'fisica',
            'ambiente' => $validated['ambiente'] ?? config('siat.ambiente', 'pruebas'),
            'estado' => 'activo',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Punto de venta creado',
            'data' => $puntoVenta->load('sucursal'),
        ], Response::HTTP_CREATED);
    }

    /**
     * Listar puntos de venta.
     */
    public function listarPuntosVenta(Request $request): JsonResponse
    {
        $query = PuntoVenta::with('sucursal');

        if ($request->sucursal_id) {
            $query->where('sucursal_id', $request->sucursal_id);
        }

        if ($request->estado) {
            $query->where('estado', $request->estado);
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('codigo_poa')->get(),
        ], Response::HTTP_OK);
    }

    /**
     * Solicitar/renovar CUIS y CUFD de un punto de venta.
     */
    public function sesiones(Request $request, PuntoVenta $puntoVenta): JsonResponse
    {
        $usuarioId = $request->user()->id;

        $cuis = $this->facturaService->solicitarCuis($puntoVenta, $usuarioId);
        $cufd = $this->facturaService->solicitarCufd($puntoVenta, $usuarioId);

        return response()->json([
            'success' => true,
            'data' => [
                'punto_venta' => $puntoVenta,
                'cuis' => $cuis,
                'cufd' => $cufd,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Bitácora de transacciones con el provider fiscal.
     */
    public function transacciones(Request $request): JsonResponse
    {
        $query = SiatTransaccion::with(['factura', 'puntoVenta']);

        if ($request->tipo_operacion) {
            $query->where('tipo_operacion', $request->tipo_operacion);
        }

        if ($request->estado) {
            $query->where('estado', $request->estado);
        }

        if ($request->cuf) {
            $query->where('cuf', $request->cuf);
        }

        $query->orderByDesc('created_at');
        $perPage = min($request->per_page ?? 15, 100);

        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage),
        ], Response::HTTP_OK);
    }

    /**
     * Guardar cifrada una credencial SIAT para su uso futuro.
     */
    public function guardarCredencial(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'valor' => ['required', 'string'],
            'ambiente' => ['nullable', 'in:pruebas,produccion'],
        ]);

        \App\Models\SiatCredencial::guardarCifrada(
            $validated['nombre'],
            $validated['valor'],
            $validated['ambiente'] ?? 'pruebas'
        );

        return response()->json([
            'success' => true,
            'message' => 'Credencial guardada cifrada (nunca se devuelve en texto plano)',
            'data' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Estado de configuración del SIAT.
     */
    public function estadoConfiguracion(Request $request): JsonResponse
    {
        $ambiente = config('siat.ambiente');
        $credenciales = \App\Models\SiatCredencial::where('estado', 'activa')
            ->get(['nombre', 'ambiente', 'updated_at'])
            ->map(fn ($c) => [
                'nombre' => $c->nombre,
                'ambiente' => $c->ambiente,
                'actualizada' => $c->updated_at?->toIso8601String(),
                'cifrada' => true,
            ])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'provider' => config('siat.provider'),
                'provider_simulado' => app(\App\Contracts\FiscalProvider::class)->esSimulado(),
                'ambiente' => $ambiente,
                'codigo_sistema' => config('siat.codigo_sistema'),
                'cuarentena' => (bool) config('siat.cuarentena'),
                'credenciales' => $credenciales,
                'mensaje' => 'La activación real requiere credenciales SIAT, firma digital, catálogos vigentes, ambiente piloto y homologación con el SIN.',
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Representación gráfica de la factura (datos de impresión y QR).
     */
    public function representacion(Request $request, Factura $factura): JsonResponse
    {
        $factura->load(['venta', 'puntoVenta', 'sucursal', 'usuario', 'detalles.producto']);

        $empresa = Empresa::obtenerODefault();

        return response()->json([
            'success' => true,
            'data' => [
                'encabezado' => [
                    'nit' => $empresa->nit,
                    'razon_social' => $empresa->razon_social,
                    'direccion' => $empresa->direccion,
                    'telefono' => $empresa->telefono,
                    'municipio' => $empresa->municipio,
                    'actividad' => $empresa->descripcion_actividad,
                ],
                'factura' => $this->formatear($factura, false),
                'qr' => $factura->qr,
                'leyenda' => $factura->leyenda,
                'pie' => [
                    'mensaje' => 'Este comprobante fue generado con facturación simulada. Para operación real se requiere credenciales SIAT, firma digital, ambiente piloto y homologación con el SIN.',
                ],
            ],
        ], Response::HTTP_OK);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatear(Factura $f, bool $resumido): array
    {
        $datos = [
            'id' => $f->id,
            'numero_factura' => $f->numero_factura,
            'cuf' => $f->cuf,
            'cufd' => $f->cufd,
            'cuis' => $f->cuis,
            'numero_autorizacion' => $f->numero_autorizacion,
            'codigo_control' => $f->codigo_control,
            'tipo_emision' => $f->tipo_emision,
            'tipo_documento_sector' => $f->tipo_documento_sector,
            'tipo_pago' => $f->tipo_pago,
            'nit_cliente' => $f->nit_cliente,
            'razon_social_cliente' => $f->razon_social_cliente,
            'complemento_ci' => $f->complemento_ci,
            'direccion_cliente' => $f->direccion_cliente,
            'fecha_emision' => $f->fecha_emision?->toDateString(),
            'leyenda' => $f->leyenda,
            'subtotal' => (float) $f->subtotal,
            'descuento' => (float) $f->descuento,
            'monto_total' => (float) $f->monto_total,
            'monto_sujeto_iva' => (float) $f->monto_sujeto_iva,
            'monto_no_sujeto' => (float) $f->monto_no_sujeto,
            'monto_ice' => (float) $f->monto_ice,
            'estado' => $f->estado,
            'motivo_anulacion' => $f->motivo_anulacion,
            'fecha_anulacion' => $f->fecha_anulacion,
            'qr' => $f->qr,
            'es_simulada' => true,
        ];

        if ($resumido) {
            $datos['venta'] = $f->venta ? ['id' => $f->venta->id, 'numero_venta' => $f->venta->numero_venta, 'total' => (float) $f->venta->total] : null;
            $datos['punto_venta'] = $f->puntoVenta ? ['id' => $f->puntoVenta->id, 'codigo_poa' => $f->puntoVenta->codigo_poa, 'nombre' => $f->puntoVenta->nombre] : null;
            $datos['sucursal'] = $f->sucursal ? ['id' => $f->sucursal->id, 'nombre' => $f->sucursal->nombre] : null;

            return $datos;
        }

        $datos['venta'] = $f->venta;
        $datos['punto_venta'] = $f->puntoVenta;
        $datos['sucursal'] = $f->sucursal;
        $datos['usuario'] = $f->usuario ? ['id' => $f->usuario->id, 'nombre' => $f->usuario->nombre, 'apellidos' => $f->usuario->apellidos] : null;
        $datos['detalles'] = $f->detalles->map(fn ($d) => [
            'id' => $d->id,
            'producto_id' => $d->producto_id,
            'codigo_producto' => $d->codigo_producto,
            'descripcion' => $d->descripcion,
            'producto' => $d->producto ? ['id' => $d->producto->id, 'nombre' => $d->producto->nombre] : null,
            'cantidad' => (int) $d->cantidad,
            'precio_unitario' => (float) $d->precio_unitario,
            'descuento_unitario' => (float) $d->descuento_unitario,
            'subtotal' => (float) $d->subtotal,
        ])->values();

        return $datos;
    }
}