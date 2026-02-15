<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CotizacionController extends Controller
{
    #[OA\Get(
        path: '/api/cotizaciones',
        summary: 'Listar todas las cotizaciones',
        tags: ['Cotizaciones'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de cotizaciones',
            ),
        ]
    )]
    public function index(Request $request)
    {
        try {
            $query = Cotizacion::query();

            // Filtro por búsqueda
            if ($request->has('q')) {
                $search = $request->input('q');
                $query->where('numero_cotizacion', 'like', "%$search%")
                    ->orWhere('cliente', 'like', "%$search%");
            }

            // Filtro por estado
            if ($request->has('estado') && $request->input('estado') !== 'todos') {
                $query->where('estado', $request->input('estado'));
            }

            $cotizaciones = $query->with('productos.producto')->orderBy('fecha', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $cotizaciones,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener cotizaciones: '.$e->getMessage(),
            ], 500);
        }
    }

    #[OA\Post(
        path: '/api/cotizaciones',
        summary: 'Crear una nueva cotización',
        tags: ['Cotizaciones'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'numero_cotizacion', type: 'string'),
                    new OA\Property(property: 'cliente', type: 'string'),
                    new OA\Property(property: 'fecha', type: 'string', format: 'date'),
                    new OA\Property(property: 'fecha_vencimiento', type: 'string', format: 'date'),
                    new OA\Property(property: 'subtotal', type: 'number'),
                    new OA\Property(property: 'impuesto', type: 'number'),
                    new OA\Property(property: 'descuento', type: 'number'),
                    new OA\Property(property: 'total', type: 'number'),
                    new OA\Property(property: 'descripcion', type: 'string'),
                    new OA\Property(property: 'notas', type: 'string'),
                ],
                required: ['numero_cotizacion', 'cliente', 'fecha', 'fecha_vencimiento', 'total']
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Cotización creada'),
            new OA\Response(response: 422, description: 'Validación fallida'),
        ]
    )]
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'numero_cotizacion' => 'required|string|unique:cotizacions',
                'cliente' => 'required|string',
                'fecha' => 'required|date',
                'fecha_vencimiento' => 'required|date',
                'subtotal' => 'nullable|numeric|min:0',
                'impuesto' => 'nullable|numeric|min:0',
                'descuento' => 'nullable|numeric|min:0',
                'total' => 'required|numeric|min:0',
                'estado' => 'nullable|in:en_espera,aceptada,rechazada,expirada',
                'descripcion' => 'nullable|string',
                'notas' => 'nullable|string',
                'productos' => 'required|array|min:1',
                'productos.*.producto_id' => 'required|uuid|exists:productos,id',
                'productos.*.cantidad' => 'required|integer|min:1',
                'productos.*.precio_unitario' => 'required|numeric|min:0',
            ]);

            $validated['estado'] = $validated['estado'] ?? 'en_espera';
            $productos = $validated['productos'];
            unset($validated['productos']);

            $cotizacion = Cotizacion::create($validated);

            // Guardar productos de la cotización
            foreach ($productos as $producto) {
                $cotizacion->productos()->create([
                    'producto_id' => $producto['producto_id'],
                    'cantidad' => $producto['cantidad'],
                    'precio_unitario' => $producto['precio_unitario'],
                ]);
            }

            // Cargar relación de productos para la respuesta
            $cotizacion->load('productos.producto');

            return response()->json([
                'success' => true,
                'data' => $cotizacion,
                'message' => 'Cotización creada correctamente',
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la cotización: '.$e->getMessage(),
            ], 500);
        }
    }

    #[OA\Get(
        path: '/api/cotizaciones/{id}',
        summary: 'Obtener una cotización específica',
        tags: ['Cotizaciones'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cotización encontrada'),
            new OA\Response(response: 404, description: 'Cotización no encontrada'),
        ]
    )]
    public function show(Cotizacion $cotizacion)
    {
        try {
            $cotizacion->load('productos.producto');

            return response()->json([
                'success' => true,
                'data' => $cotizacion,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la cotización: '.$e->getMessage(),
            ], 500);
        }
    }

    #[OA\Put(
        path: '/api/cotizaciones/{id}',
        summary: 'Actualizar una cotización',
        tags: ['Cotizaciones'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cotización actualizada'),
            new OA\Response(response: 404, description: 'Cotización no encontrada'),
        ]
    )]
    public function update(Request $request, Cotizacion $cotizacion)
    {
        try {
            $validated = $request->validate([
                'numero_cotizacion' => 'sometimes|required|string|unique:cotizacions,numero_cotizacion,'.$cotizacion->id,
                'cliente' => 'sometimes|required|string',
                'fecha' => 'sometimes|required|date',
                'fecha_vencimiento' => 'sometimes|required|date',
                'subtotal' => 'nullable|numeric|min:0',
                'impuesto' => 'nullable|numeric|min:0',
                'descuento' => 'nullable|numeric|min:0',
                'total' => 'sometimes|required|numeric|min:0',
                'estado' => 'nullable|in:en_espera,aceptada,rechazada,expirada',
                'descripcion' => 'nullable|string',
                'notas' => 'nullable|string',
                'productos' => 'nullable|array|min:1',
                'productos.*.producto_id' => 'required_with:productos|uuid|exists:productos,id',
                'productos.*.cantidad' => 'required_with:productos|integer|min:1',
                'productos.*.precio_unitario' => 'required_with:productos|numeric|min:0',
            ]);

            $productos = $validated['productos'] ?? null;
            unset($validated['productos']);

            $cotizacion->update($validated);

            // Actualizar productos si se proporcionan
            if ($productos !== null) {
                $cotizacion->productos()->delete();
                foreach ($productos as $producto) {
                    $cotizacion->productos()->create([
                        'producto_id' => $producto['producto_id'],
                        'cantidad' => $producto['cantidad'],
                        'precio_unitario' => $producto['precio_unitario'],
                    ]);
                }
            }

            $cotizacion->load('productos.producto');

            return response()->json([
                'success' => true,
                'data' => $cotizacion,
                'message' => 'Cotización actualizada correctamente',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la cotización: '.$e->getMessage(),
            ], 500);
        }
    }

    #[OA\Delete(
        path: '/api/cotizaciones/{id}',
        summary: 'Eliminar una cotización',
        tags: ['Cotizaciones'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cotización eliminada'),
            new OA\Response(response: 404, description: 'Cotización no encontrada'),
        ]
    )]
    public function destroy(Cotizacion $cotizacion)
    {
        try {
            $cotizacion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cotización eliminada correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la cotización: '.$e->getMessage(),
            ], 500);
        }
    }

    #[OA\Patch(
        path: '/api/cotizaciones/{id}/cambiar-estado',
        summary: 'Cambiar estado de una cotización',
        tags: ['Cotizaciones'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'estado', type: 'string', enum: ['en_espera', 'aceptada', 'rechazada', 'expirada']),
                ],
                required: ['estado']
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Estado actualizado'),
            new OA\Response(response: 404, description: 'Cotización no encontrada'),
        ]
    )]
    public function cambiarEstado(Request $request, Cotizacion $cotizacion)
    {
        try {
            $validated = $request->validate([
                'estado' => 'required|in:en_espera,aceptada,rechazada,expirada',
            ]);

            $cotizacion->update(['estado' => $validated['estado']]);

            return response()->json([
                'success' => true,
                'data' => $cotizacion,
                'message' => 'Estado actualizado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado: '.$e->getMessage(),
            ], 500);
        }
    }
}
