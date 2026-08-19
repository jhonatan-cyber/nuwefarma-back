<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AjusteInventario;
use App\Models\AjusteInventarioDetalle;
use App\Models\Lote;
use App\Models\MovimientoLote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Ajustes de Inventario', description: 'Gestión de ajustes de inventario')]
class AjusteInventarioController extends Controller
{
    #[OA\Get(
        path: '/api/ajustes-inventario',
        summary: 'Listar ajustes de inventario',
        security: [['bearerAuth' => []]],
        tags: ['Ajustes de Inventario'],
        parameters: [
            new OA\Parameter(name: 'tipo', in: 'query', description: 'Filtrar por tipo', required: false),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de ajustes'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = AjusteInventario::with(['usuario', 'sucursal', 'detalles.lote.producto'])
            ->recientes();

        if ($tipo = $request->query('tipo')) {
            $query->porTipo($tipo);
        }

        $ajustes = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $ajustes,
        ]);
    }

    #[OA\Post(
        path: '/api/ajustes-inventario',
        summary: 'Crear ajuste de inventario',
        security: [['bearerAuth' => []]],
        tags: ['Ajustes de Inventario'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['tipo', 'motivo', 'items'],
                properties: [
                    new OA\Property(property: 'tipo', type: 'string', enum: ['incremento', 'decremento']),
                    new OA\Property(property: 'motivo', type: 'string'),
                    new OA\Property(property: 'observaciones', type: 'string'),
                    new OA\Property(property: 'sucursal_id', type: 'string', format: 'uuid'),
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'lote_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'stock_nuevo', type: 'integer', minimum: 0),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Ajuste creado'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tipo' => 'required|in:incremento,decremento',
            'motivo' => 'required|string|max:100',
            'observaciones' => 'nullable|string|max:500',
            'sucursal_id' => 'nullable|uuid|exists:sucursals,id',
            'items' => 'required|array|min:1',
            'items.*.lote_id' => 'required|uuid|exists:lotes,id',
            'items.*.stock_nuevo' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        try {
            DB::beginTransaction();

            $ajuste = AjusteInventario::create([
                'tipo' => $request->tipo,
                'motivo' => $request->motivo,
                'observaciones' => $request->observaciones,
                'usuario_id' => Auth::user()?->id,
                'sucursal_id' => $request->sucursal_id,
            ]);

            foreach ($request->items as $item) {
                $lote = Lote::query()->lockForUpdate()->findOrFail($item['lote_id']);
                $stockAnterior = $lote->stock;
                $stockNuevo = $item['stock_nuevo'];
                $diferencia = $stockNuevo - $stockAnterior;

                if ($diferencia === 0) {
                    continue;
                }

                AjusteInventarioDetalle::create([
                    'ajuste_id' => $ajuste->id,
                    'lote_id' => $lote->id,
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => $stockNuevo,
                    'diferencia' => $diferencia,
                ]);

                if ($diferencia > 0) {
                    $lote->aumentarStock($diferencia, $lote->precio_costo);

                    MovimientoLote::create([
                        'lote_id' => $lote->id,
                        'tipo_movimiento' => MovimientoLote::AJUSTE_POSITIVO,
                        'cantidad' => $diferencia,
                        'stock_anterior' => $stockAnterior,
                        'stock_nuevo' => $stockNuevo,
                        'documento_tipo' => 'AjusteInventario',
                        'documento_id' => $ajuste->id,
                        'observaciones' => "Ajuste: {$request->motivo}",
                    ]);
                } else {
                    $lote->descontarStock(abs($diferencia));

                    MovimientoLote::create([
                        'lote_id' => $lote->id,
                        'tipo_movimiento' => MovimientoLote::AJUSTE_NEGATIVO,
                        'cantidad' => abs($diferencia),
                        'stock_anterior' => $stockAnterior,
                        'stock_nuevo' => $stockNuevo,
                        'documento_tipo' => 'AjusteInventario',
                        'documento_id' => $ajuste->id,
                        'observaciones' => "Ajuste: {$request->motivo}",
                    ]);
                }
            }

            $ajuste->load(['usuario', 'sucursal', 'detalles.lote.producto']);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $ajuste,
                'message' => 'Ajuste de inventario creado exitosamente',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el ajuste: '.$e->getMessage(),
            ], 500);
        }
    }

    #[OA\Get(
        path: '/api/ajustes-inventario/{id}',
        summary: 'Obtener ajuste por ID',
        security: [['bearerAuth' => []]],
        tags: ['Ajustes de Inventario'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Datos del ajuste'),
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $ajuste = AjusteInventario::with(['usuario', 'sucursal', 'detalles.lote.producto'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $ajuste,
        ]);
    }
}
