<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gasto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Gastos', description: 'Gestión de gastos')]
class GastoController extends Controller
{
    #[OA\Get(
        path: '/api/gastos',
        summary: 'Listar gastos',
        security: [['bearerAuth' => []]],
        tags: ['Gastos'],
        parameters: [
            new OA\Parameter(
                name: 'estado',
                in: 'query',
                description: 'Filtrar por estado',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['activo', 'inactivo'])
            ),
            new OA\Parameter(
                name: 'categoria',
                in: 'query',
                description: 'Filtrar por categoría',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de gastos',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(type: 'object')
                        ),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Gasto::query();

        if ($estado = $request->query('estado')) {
            $query->where('estado', $estado);
        }

        if ($categoria = $request->query('categoria')) {
            $query->where('categoria', $categoria);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('descripcion', 'like', "%{$search}%")
                    ->orWhere('notas', 'like', "%{$search}%");
            });
        }

        $gastos = $query->orderBy('fecha', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $gastos,
        ]);
    }

    #[OA\Post(
        path: '/api/gastos',
        summary: 'Crear gasto',
        security: [['bearerAuth' => []]],
        tags: ['Gastos'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nombre', 'monto', 'categoria', 'fecha'],
                properties: [
                    new OA\Property(property: 'nombre', type: 'string', maxLength: 255),
                    new OA\Property(property: 'monto', type: 'number', format: 'float', minimum: 0),
                    new OA\Property(property: 'descripcion', type: 'string', nullable: true),
                    new OA\Property(property: 'categoria', type: 'string', maxLength: 255),
                    new OA\Property(property: 'notas', type: 'string', nullable: true),
                    new OA\Property(property: 'fecha', type: 'string', format: 'date'),
                    new OA\Property(property: 'estado', type: 'string', enum: ['activo', 'inactivo'], default: 'activo'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Gasto creado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object'),
                        new OA\Property(property: 'message', type: 'string', example: 'Gasto creado exitosamente'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'monto' => 'required|numeric|min:0',
                'descripcion' => 'nullable|string',
                'categoria' => 'required|string|max:255',
                'notas' => 'nullable|string',
                'fecha' => 'required|date',
                'estado' => 'in:activo,inactivo',
            ]);

            $validated['estado'] = $validated['estado'] ?? 'activo';

            $gasto = Gasto::create($validated);

            return response()->json([
                'success' => true,
                'data' => $gasto,
                'message' => 'Gasto creado exitosamente',
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el gasto',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Get(
        path: '/api/gastos/{id}',
        summary: 'Obtener gasto por ID',
        security: [['bearerAuth' => []]],
        tags: ['Gastos'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID del gasto',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Gasto encontrado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Gasto no encontrado'),
        ]
    )]
    public function show(string $id): JsonResponse
    {
        try {
            $gasto = Gasto::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $gasto,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gasto no encontrado',
            ], 404);
        }
    }

    #[OA\Put(
        path: '/api/gastos/{id}',
        summary: 'Actualizar gasto',
        security: [['bearerAuth' => []]],
        tags: ['Gastos'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID del gasto',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nombre', type: 'string', maxLength: 255),
                    new OA\Property(property: 'monto', type: 'number', format: 'float', minimum: 0),
                    new OA\Property(property: 'descripcion', type: 'string', nullable: true),
                    new OA\Property(property: 'categoria', type: 'string', maxLength: 255),
                    new OA\Property(property: 'notas', type: 'string', nullable: true),
                    new OA\Property(property: 'fecha', type: 'string', format: 'date'),
                    new OA\Property(property: 'estado', type: 'string', enum: ['activo', 'inactivo']),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Gasto actualizado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object'),
                        new OA\Property(property: 'message', type: 'string', example: 'Gasto actualizado exitosamente'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Gasto no encontrado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $gasto = Gasto::findOrFail($id);

            $validated = $request->validate([
                'nombre' => 'sometimes|required|string|max:255',
                'monto' => 'sometimes|required|numeric|min:0',
                'descripcion' => 'nullable|string',
                'categoria' => 'sometimes|required|string|max:255',
                'notas' => 'nullable|string',
                'fecha' => 'sometimes|required|date',
                'estado' => 'sometimes|in:activo,inactivo',
            ]);

            $gasto->update($validated);

            return response()->json([
                'success' => true,
                'data' => $gasto,
                'message' => 'Gasto actualizado exitosamente',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gasto no encontrado o error al actualizar',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    #[OA\Delete(
        path: '/api/gastos/{id}',
        summary: 'Eliminar gasto',
        security: [['bearerAuth' => []]],
        tags: ['Gastos'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID del gasto',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Gasto eliminado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Gasto eliminado exitosamente'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Gasto no encontrado'),
        ]
    )]
    public function destroy(string $id): JsonResponse
    {
        try {
            $gasto = Gasto::findOrFail($id);
            $gasto->delete();

            return response()->json([
                'success' => true,
                'message' => 'Gasto eliminado exitosamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gasto no encontrado',
            ], 404);
        }
    }

    #[OA\Patch(
        path: '/api/gastos/{id}/toggle-estado',
        summary: 'Cambiar estado del gasto',
        security: [['bearerAuth' => []]],
        tags: ['Gastos'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID del gasto',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Estado cambiado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object'),
                        new OA\Property(property: 'message', type: 'string', example: 'Estado actualizado exitosamente'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Gasto no encontrado'),
        ]
    )]
    public function toggleEstado(string $id): JsonResponse
    {
        try {
            $gasto = Gasto::findOrFail($id);
            $gasto->estado = $gasto->estado === 'activo' ? 'inactivo' : 'activo';
            $gasto->save();

            return response()->json([
                'success' => true,
                'data' => $gasto,
                'message' => 'Estado actualizado exitosamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gasto no encontrado',
            ], 404);
        }
    }
}
