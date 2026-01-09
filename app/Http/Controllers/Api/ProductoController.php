<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Productos', description: 'Gestión de productos/medicamentos')]
class ProductoController extends Controller
{
    #[OA\Get(
        path: '/api/productos',
        summary: 'Listar productos',
        security: [['bearerAuth' => []]],
        tags: ['Productos'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', description: 'Búsqueda por nombre, código de barras o SKU', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'estado', in: 'query', schema: new OA\Schema(type: 'string', enum: ['activo', 'inactivo'])),
            new OA\Parameter(name: 'categoria_id', in: 'query', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'bajo_stock', in: 'query', description: 'Filtrar productos bajo stock (stock_actual <= stock_minimo)', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'proximo_vencer', in: 'query', description: 'Filtrar productos que vencen pronto', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'dias_alerta', in: 'query', description: 'Días para considerar próximo a vencer (default 60)', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de productos',
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
        $query = Producto::with('categoria:id,nombre');

        if ($estado = $request->query('estado')) {
            $query->where('estado', $estado);
        }

        if ($categoriaId = $request->query('categoria_id')) {
            $query->where('categoria_id', $categoriaId);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('codigo_barras', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('codigo_interno', 'like', "%{$search}%");
            });
        }

        if (filter_var($request->query('bajo_stock'), FILTER_VALIDATE_BOOLEAN)) {
            $query->whereColumn('stock_actual', '<=', 'stock_minimo');
        }

        if (filter_var($request->query('proximo_vencer'), FILTER_VALIDATE_BOOLEAN)) {
            $dias = (int) ($request->query('dias_alerta', 60));
            $query->whereNotNull('fecha_vencimiento')
                ->where('fecha_vencimiento', '<=', now()->addDays($dias));
        }

        $productos = $query->orderBy('nombre')->get();

        return response()->json([
            'success' => true,
            'data' => $productos,
        ]);
    }

    #[OA\Post(
        path: '/api/productos',
        summary: 'Crear producto',
        security: [['bearerAuth' => []]],
        tags: ['Productos'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nombre'],
                properties: [
                    new OA\Property(property: 'nombre', type: 'string'),
                    new OA\Property(property: 'categoria_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'codigo_barras', type: 'string'),
                    new OA\Property(property: 'sku', type: 'string'),
                    new OA\Property(property: 'codigo_interno', type: 'string'),
                    new OA\Property(property: 'laboratorio', type: 'string'),
                    new OA\Property(property: 'forma_farmaceutica', type: 'string'),
                    new OA\Property(property: 'concentracion', type: 'string'),
                    new OA\Property(property: 'presentacion', type: 'string'),
                    new OA\Property(property: 'via_administracion', type: 'string'),
                    new OA\Property(property: 'unidad_medida', type: 'string'),
                    new OA\Property(property: 'fracciones_por_unidad', type: 'integer'),
                    new OA\Property(property: 'permite_fraccionar', type: 'boolean'),
                    new OA\Property(property: 'lote', type: 'string'),
                    new OA\Property(property: 'fecha_vencimiento', type: 'string', format: 'date'),
                    new OA\Property(property: 'registro_sanitario', type: 'string'),
                    new OA\Property(property: 'refrigeracion_requerida', type: 'boolean'),
                    new OA\Property(property: 'dias_para_alertar_vencimiento', type: 'integer'),
                    new OA\Property(property: 'stock_actual', type: 'integer'),
                    new OA\Property(property: 'stock_minimo', type: 'integer'),
                    new OA\Property(property: 'stock_maximo', type: 'integer'),
                    new OA\Property(property: 'precio_compra', type: 'number', format: 'float'),
                    new OA\Property(property: 'precio_venta', type: 'number', format: 'float'),
                    new OA\Property(property: 'margen_sugerido', type: 'number', format: 'float'),
                    new OA\Property(property: 'impuesto', type: 'number', format: 'float'),
                    new OA\Property(property: 'etiquetas', type: 'array', items: new OA\Items(type: 'string')),
                    new OA\Property(property: 'fotos', type: 'array', items: new OA\Items(type: 'string')),
                    new OA\Property(property: 'descripcion', type: 'string'),
                    new OA\Property(property: 'estado', type: 'string', enum: ['activo', 'inactivo']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Producto creado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateData($request);

            $producto = Producto::create($validated)->load('categoria:id,nombre');

            ActivityLog::registrar(
                accion: 'create',
                modulo: 'productos',
                registroId: $producto->id,
                descripcion: "Producto creado: {$producto->nombre}",
                datosNuevos: $producto->toArray()
            );

            return response()->json([
                'success' => true,
                'message' => 'Producto creado exitosamente',
                'data' => $producto,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    #[OA\Get(
        path: '/api/productos/{id}',
        summary: 'Ver producto',
        security: [['bearerAuth' => []]],
        tags: ['Productos'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        responses: [new OA\Response(response: 200, description: 'Detalle de producto'), new OA\Response(response: 404, description: 'No encontrado')]
    )]
    public function show(string $id): JsonResponse
    {
        $producto = Producto::with('categoria:id,nombre')->find($id);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $producto,
        ]);
    }

    #[OA\Put(
        path: '/api/productos/{id}',
        summary: 'Actualizar producto',
        security: [['bearerAuth' => []]],
        tags: ['Productos'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent()),
        responses: [new OA\Response(response: 200, description: 'Producto actualizado'), new OA\Response(response: 404, description: 'No encontrado')]
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado',
            ], 404);
        }

        try {
            $validated = $this->validateData($request, $producto->id);

            $datosAnteriores = $producto->toArray();
            $producto->update($validated);
            $producto->load('categoria:id,nombre');

            ActivityLog::registrar(
                accion: 'update',
                modulo: 'productos',
                registroId: $producto->id,
                descripcion: "Producto actualizado: {$producto->nombre}",
                datosAnteriores: $datosAnteriores,
                datosNuevos: $producto->toArray()
            );

            return response()->json([
                'success' => true,
                'message' => 'Producto actualizado exitosamente',
                'data' => $producto,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    #[OA\Delete(
        path: '/api/productos/{id}',
        summary: 'Eliminar producto',
        security: [['bearerAuth' => []]],
        tags: ['Productos'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        responses: [new OA\Response(response: 200, description: 'Producto eliminado'), new OA\Response(response: 404, description: 'No encontrado')]
    )]
    public function destroy(string $id): JsonResponse
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado',
            ], 404);
        }

        $datosAnteriores = $producto->toArray();
        $producto->delete();

        ActivityLog::registrar(
            accion: 'delete',
            modulo: 'productos',
            registroId: $id,
            descripcion: "Producto eliminado: {$datosAnteriores['nombre']}",
            datosAnteriores: $datosAnteriores
        );

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado exitosamente',
        ]);
    }

    #[OA\Patch(
        path: '/api/productos/{id}/toggle-estado',
        summary: 'Activar/Desactivar producto',
        security: [['bearerAuth' => []]],
        tags: ['Productos'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        responses: [new OA\Response(response: 200, description: 'Estado actualizado'), new OA\Response(response: 404, description: 'No encontrado')]
    )]
    public function toggleEstado(string $id): JsonResponse
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado',
            ], 404);
        }

        $estadoAnterior = $producto->estado;
        $producto->estado = $producto->estado === 'activo' ? 'inactivo' : 'activo';
        $producto->save();

        ActivityLog::registrar(
            accion: 'toggle_estado',
            modulo: 'productos',
            registroId: $producto->id,
            descripcion: "Estado de producto cambiado: {$producto->nombre} de {$estadoAnterior} a {$producto->estado}"
        );

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado exitosamente',
            'data' => $producto,
        ]);
    }

    /**
     * Validación centralizada para create/update
     */
    private function validateData(Request $request, ?string $id = null): array
    {
        $uniqueRule = fn(string $column) => Rule::unique('productos', $column)->ignore($id);

        return $request->validate([
            'categoria_id' => ['nullable', 'exists:categorias,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'codigo_barras' => ['nullable', 'string', 'max:100', $uniqueRule('codigo_barras')],
            'sku' => ['nullable', 'string', 'max:100', $uniqueRule('sku')],
            'codigo_interno' => ['nullable', 'string', 'max:100', $uniqueRule('codigo_interno')],
            'laboratorio' => ['nullable', 'string', 'max:255'],
            'forma_farmaceutica' => ['nullable', 'string', 'max:255'],
            'concentracion' => ['nullable', 'string', 'max:255'],
            'presentacion' => ['nullable', 'string', 'max:255'],
            'via_administracion' => ['nullable', 'string', 'max:255'],
            'unidad_medida' => ['nullable', 'string', 'max:100'],
            'fracciones_por_unidad' => ['nullable', 'integer', 'min:1'],
            'permite_fraccionar' => ['boolean'],
            'lote' => ['nullable', 'string', 'max:255'],
            'fecha_vencimiento' => ['nullable', 'date'],
            'registro_sanitario' => ['nullable', 'string', 'max:255'],
            'refrigeracion_requerida' => ['boolean'],
            'dias_para_alertar_vencimiento' => ['integer', 'min:0'],
            'stock_actual' => ['integer', 'min:0'],
            'stock_minimo' => ['integer', 'min:0'],
            'stock_maximo' => ['nullable', 'integer', 'min:0'],
            'precio_compra' => ['numeric', 'min:0'],
            'precio_venta' => ['numeric', 'min:0'],
            'margen_sugerido' => ['nullable', 'numeric', 'min:0'],
            'impuesto' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'etiquetas' => ['nullable', 'array'],
            'etiquetas.*' => ['string'],
            'fotos' => ['nullable', 'array'],
            'fotos.*' => ['string'],
            'descripcion' => ['nullable', 'string'],
            'estado' => ['nullable', Rule::in(['activo', 'inactivo'])],
        ]);
    }
}
