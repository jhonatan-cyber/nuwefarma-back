<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lote;
use App\Models\MovimientoLote;
use App\Models\Traslado;
use App\Models\TrasladoDetalle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Traslados', description: 'Gestión de traslados entre sucursales')]
class TrasladoController extends Controller
{
    #[OA\Get(
        path: '/api/traslados',
        summary: 'Listar traslados',
        security: [['bearerAuth' => []]],
        tags: ['Traslados'],
        parameters: [
            new OA\Parameter(name: 'estado', in: 'query', description: 'Filtrar por estado', required: false),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de traslados'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Traslado::with(['sucursalOrigen', 'sucursalDestino', 'usuarioSolicita', 'detalles.loteOrigen.producto'])
            ->recientes();

        if ($estado = $request->query('estado')) {
            $query->porEstado($estado);
        }

        $traslados = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $traslados,
        ]);
    }

    #[OA\Post(
        path: '/api/traslados',
        summary: 'Crear traslado',
        security: [['bearerAuth' => []]],
        tags: ['Traslados'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['sucursal_destino_id', 'items'],
                properties: [
                    new OA\Property(property: 'sucursal_destino_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'observaciones', type: 'string'),
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'lote_origen_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'cantidad', type: 'integer', minimum: 1),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Traslado creado'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sucursal_destino_id' => 'required|uuid|exists:sucursals,id',
            'observaciones' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.lote_origen_id' => 'required|uuid|exists:lotes,id',
            'items.*.cantidad' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        try {
            DB::beginTransaction();

            $traslado = Traslado::create([
                'numero_traslado' => Traslado::generateNumeroTraslado(),
                'sucursal_origen_id' => $request->user()?->sucursal_id,
                'sucursal_destino_id' => $request->sucursal_destino_id,
                'usuario_solicita_id' => $request->user()?->id,
                'estado' => Traslado::ESTADO_PENDIENTE,
                'observaciones' => $request->observaciones,
            ]);

            foreach ($request->items as $item) {
                $loteOrigen = Lote::query()->lockForUpdate()->findOrFail($item['lote_origen_id']);

                if ($loteOrigen->stock < $item['cantidad']) {
                    throw new \Exception("Stock insuficiente en lote {$loteOrigen->numero_lote}");
                }

                TrasladoDetalle::create([
                    'traslado_id' => $traslado->id,
                    'lote_origen_id' => $loteOrigen->id,
                    'cantidad' => $item['cantidad'],
                ]);
            }

            $traslado->load(['sucursalOrigen', 'sucursalDestino', 'usuarioSolicita', 'detalles.loteOrigen.producto']);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $traslado,
                'message' => 'Traslado creado exitosamente',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el traslado: '.$e->getMessage(),
            ], 500);
        }
    }

    #[OA\Patch(
        path: '/api/traslados/{id}/enviar',
        summary: 'Enviar traslado',
        security: [['bearerAuth' => []]],
        tags: ['Traslados'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Traslado enviado'),
        ]
    )]
    public function enviar(string $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Lock de fila: serializa la transición y evita doble procesamiento
            $traslado = Traslado::query()->lockForUpdate()->findOrFail($id);

            if (! $traslado->puedeSerEnviado()) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'El traslado no puede ser enviado en su estado actual',
                ], 422);
            }

            foreach ($traslado->detalles as $detalle) {
                $loteOrigen = Lote::query()->lockForUpdate()->findOrFail($detalle->lote_origen_id);

                $stockAnterior = $loteOrigen->stock;
                $loteOrigen->descontarStock($detalle->cantidad);

                MovimientoLote::create([
                    'lote_id' => $loteOrigen->id,
                    'tipo_movimiento' => MovimientoLote::SALIDA_TRASLADO_OUT,
                    'cantidad' => $detalle->cantidad,
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => $loteOrigen->stock,
                    'documento_tipo' => 'Traslado',
                    'documento_id' => $traslado->id,
                    'documento_numero' => $traslado->numero_traslado,
                    'observaciones' => "Traslado a {$traslado->sucursalDestino->nombre}",
                ]);
            }

            $traslado->update([
                'estado' => Traslado::ESTADO_ENVIADO,
                'usuario_autoriza_id' => Auth::user()?->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $traslado,
                'message' => 'Traslado enviado exitosamente',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el traslado: '.$e->getMessage(),
            ], 500);
        }
    }

    #[OA\Patch(
        path: '/api/traslados/{id}/recibir',
        summary: 'Recibir traslado',
        security: [['bearerAuth' => []]],
        tags: ['Traslados'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Traslado recibido'),
        ]
    )]
    public function recibir(string $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Lock de fila: serializa la transición y evita doble procesamiento
            $traslado = Traslado::query()->lockForUpdate()->findOrFail($id);

            if (! $traslado->puedeSerRecibido()) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'El traslado no puede ser recibido en su estado actual',
                ], 422);
            }

            foreach ($traslado->detalles as $detalle) {
                $loteOrigen = Lote::query()->lockForUpdate()->findOrFail($detalle->lote_origen_id);

                $loteDestino = Lote::where('producto_id', $loteOrigen->producto_id)
                    ->where('sucursal_id', $traslado->sucursal_destino_id)
                    ->lockForUpdate()
                    ->first();

                if (! $loteDestino) {
                    $loteDestino = Lote::create([
                        'producto_id' => $loteOrigen->producto_id,
                        'numero_lote' => $loteOrigen->numero_lote.'-TR',
                        'stock' => $detalle->cantidad,
                        'precio_costo' => $loteOrigen->precio_costo,
                        'precio_venta' => $loteOrigen->precio_venta,
                        'fecha_fabricacion' => $loteOrigen->fecha_fabricacion,
                        'fecha_vencimiento' => $loteOrigen->fecha_vencimiento,
                        'sucursal_id' => $traslado->sucursal_destino_id,
                    ]);
                } else {
                    $loteDestino->stock += $detalle->cantidad;
                    $loteDestino->save();
                }

                $detalle->update(['lote_destino_id' => $loteDestino->id]);

                MovimientoLote::create([
                    'lote_id' => $loteDestino->id,
                    'tipo_movimiento' => MovimientoLote::ENTRADA_TRASLADO_IN,
                    'cantidad' => $detalle->cantidad,
                    'stock_anterior' => $loteDestino->stock - $detalle->cantidad,
                    'stock_nuevo' => $loteDestino->stock,
                    'documento_tipo' => 'Traslado',
                    'documento_id' => $traslado->id,
                    'documento_numero' => $traslado->numero_traslado,
                    'observaciones' => "Recepción de {$traslado->sucursalOrigen->nombre}",
                ]);
            }

            $traslado->update([
                'estado' => Traslado::ESTADO_RECIBIDO,
                'usuario_recibe_id' => Auth::user()?->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $traslado,
                'message' => 'Traslado recibido exitosamente',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al recibir el traslado: '.$e->getMessage(),
            ], 500);
        }
    }

    #[OA\Get(
        path: '/api/traslados/{id}',
        summary: 'Obtener traslado por ID',
        security: [['bearerAuth' => []]],
        tags: ['Traslados'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Datos del traslado'),
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $traslado = Traslado::with([
            'sucursalOrigen',
            'sucursalDestino',
            'usuarioSolicita',
            'usuarioAutoriza',
            'usuarioRecibe',
            'detalles.loteOrigen.producto',
            'detalles.loteDestino',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $traslado,
        ]);
    }
}
