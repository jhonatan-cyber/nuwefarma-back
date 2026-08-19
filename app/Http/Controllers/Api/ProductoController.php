<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Producto\ProductoCollection;
use App\Http\Resources\Producto\ProductoResource;
use App\Models\Producto;
use App\Actions\Product\CreateProductoAction;
use App\Actions\Product\UpdateProductoAction;
use App\Actions\Product\DeleteProductoAction;
use App\Actions\Product\ListProductosAction;
use App\Actions\Product\BulkUpdateProductosAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductoController extends Controller
{
    // Eliminar inyección de dependencias complejas por ahora
    // public function __construct(
    //     private CreateProductoAction $createProductoAction,
    //     private UpdateProductoAction $updateProductoAction,
    //     private DeleteProductoAction $deleteProductoAction,
    //     private ListProductosAction $listProductosAction,
    //     private BulkUpdateProductosAction $bulkUpdateProductosAction
    // ) {}

    /**
     * Display a paginated listing of products with filtering.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Producto::with(['categoria']);

        // Aplicar filtros básicos
        if ($request->q || $request->search) {
            $searchTerm = $request->q ?? $request->search;
            $query->where('nombre', 'like', "%{$searchTerm}%")
                  ->orWhere('codigo_barras', 'like', "%{$searchTerm}%");
        }

        if ($request->estado) {
            $query->where('estado', $request->estado);
        }

        if ($request->categoria_id) {
            $query->where('categoria_id', $request->categoria_id);
        }

        // Filtros específicos
        if ($request->stock_bajo) {
            $query->whereColumn('stock_actual', '<=', 'stock_minimo');
        }

        if ($request->proximos_vencer) {
            $dias = $request->dias_alerta ?? 30;
            $query->where('fecha_vencimiento', '<=', now()->addDays($dias))
                  ->where('fecha_vencimiento', '>', now());
        }

        // Ordenamiento
        $sort = $request->sort ?? 'nombre';
        $direction = $request->direction ?? 'asc';
        $query->orderBy($sort, $direction);

        $perPage = min($request->per_page ?? 15, 100);
        $productos = $query->paginate($perPage);

        $response = response()->json([
            'success' => true,
            'data' => new ProductoCollection($productos)
        ], Response::HTTP_OK);

        // Agregar headers de seguridad y versión
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-API-Version', '2.0');

        return $response;
    }

    /**
     * Store a newly created product in storage.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Agregar validación
            $validated = $request->validate([
                'nombre' => ['required', 'string', 'max:255'],
                'categoria_id' => ['nullable', 'exists:categorias,id'],
                'proveedor_id' => ['nullable', 'exists:proveedores,id'],
                'principio_activo' => ['nullable', 'string', 'max:255'],
                'condicion_venta' => ['nullable', 'in:venta_libre,con_receta,receta_retenida'],
                'codigo_barras' => ['nullable', 'string', 'max:50'],
                'sku' => ['nullable', 'string', 'max:50'],
                'laboratorio' => ['nullable', 'string', 'max:255'],
                'forma_farmaceutica' => ['nullable', 'string', 'max:100'],
                'concentracion' => ['nullable', 'string', 'max:100'],
                'presentacion' => ['nullable', 'string', 'max:100'],
                'registro_sanitario' => ['nullable', 'string', 'max:100'],
                'fecha_vencimiento' => ['nullable', 'date'],
                'descripcion' => ['nullable', 'string'],
                'fotos' => ['nullable', 'array'],
                'fotos.*' => ['string', 'max:2048'],
                'stock_actual' => ['nullable', 'integer', 'min:0'],
                'stock_minimo' => ['nullable', 'integer', 'min:0'],
                'precio_compra' => ['nullable', 'numeric', 'min:0'],
                'precio_venta' => ['nullable', 'numeric', 'min:0'],
                'impuesto' => ['nullable', 'numeric', 'min:0'],
                'estado' => ['nullable', 'in:activo,inactivo,descontinuado'],
            ]);

            // Simplificar sin DTOs y Value Objects por ahora
            $producto = Producto::create([
                'nombre' => $validated['nombre'],
                'categoria_id' => $validated['categoria_id'] ?? null,
                'proveedor_id' => $validated['proveedor_id'] ?? null,
                'principio_activo' => $validated['principio_activo'] ?? null,
                'condicion_venta' => $validated['condicion_venta'] ?? 'venta_libre',
                'codigo_barras' => $validated['codigo_barras'] ?? null,
                'sku' => $validated['sku'] ?? null,
                'laboratorio' => $validated['laboratorio'] ?? null,
                'forma_farmaceutica' => $validated['forma_farmaceutica'] ?? null,
                'concentracion' => $validated['concentracion'] ?? null,
                'presentacion' => $validated['presentacion'] ?? null,
                'registro_sanitario' => $validated['registro_sanitario'] ?? null,
                'fecha_vencimiento' => $validated['fecha_vencimiento'] ?? null,
                'descripcion' => $validated['descripcion'] ?? null,
                'fotos' => $validated['fotos'] ?? null,
                'stock_actual' => $validated['stock_actual'] ?? 0,
                'stock_minimo' => $validated['stock_minimo'] ?? 10,
                'precio_compra' => $validated['precio_compra'] ?? 0,
                'precio_venta' => $validated['precio_venta'] ?? 0,
                'impuesto' => $validated['impuesto'] ?? 0,
                'estado' => $validated['estado'] ?? 'activo',
            ]);

            $response = response()->json([
                'success' => true,
                'message' => 'Producto creado exitosamente',
                'data' => new ProductoResource($producto->load(['categoria', 'proveedor'])->loadCount(['lotes', 'ventaProductos', 'compraProductos']))
            ], Response::HTTP_CREATED);

            // Agregar headers de seguridad y versión
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('X-API-Version', '2.0');

            return $response;
        } catch (\Illuminate\Validation\ValidationException $e) {
            $response = response()->json([
                'success' => false,
                'message' => 'Error de validación en los datos enviados',
                'errors' => $e->errors()
            ], 422);

            // Agregar headers de seguridad y versión
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('X-API-Version', '2.0');

            return $response;
        }
    }

    /**
     * Display the specified product with relationships.
     * 
     * @param Producto $producto
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $producto = Producto::with(['categoria', 'proveedor'])
                ->withCount(['lotes', 'ventaProductos', 'compraProductos'])
                ->findOrFail($id);
            
            $response = response()->json([
                'success' => true,
                'data' => new ProductoResource($producto)
            ], Response::HTTP_OK);

            // Agregar headers de seguridad y versión
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('X-API-Version', '2.0');

            return $response;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $response = response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);

            // Agregar headers de seguridad y versión
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('X-API-Version', '2.0');

            return $response;
        }
    }

    /**
     * Update the specified product in storage.
     * 
     * @param Request $request
     * @param Producto $producto
     * @return JsonResponse
     */
    public function update(Request $request, Producto $producto): JsonResponse
    {
        // Simplificar sin DTOs y Value Objects por ahora
        $producto->update([
            'nombre' => $request->nombre ?? $producto->nombre,
            'categoria_id' => $request->has('categoria_id') ? $request->categoria_id : $producto->categoria_id,
            'proveedor_id' => $request->has('proveedor_id') ? $request->proveedor_id : $producto->proveedor_id,
            'principio_activo' => $request->has('principio_activo') ? $request->principio_activo : $producto->principio_activo,
            'condicion_venta' => $request->has('condicion_venta') ? $request->condicion_venta : $producto->condicion_venta,
            'codigo_barras' => $request->codigo_barras ?? $producto->codigo_barras,
            'sku' => $request->sku ?? $producto->sku,
            'laboratorio' => $request->laboratorio ?? $producto->laboratorio,
            'forma_farmaceutica' => $request->forma_farmaceutica ?? $producto->forma_farmaceutica,
            'concentracion' => $request->concentracion ?? $producto->concentracion,
            'presentacion' => $request->presentacion ?? $producto->presentacion,
            'registro_sanitario' => $request->registro_sanitario ?? $producto->registro_sanitario,
            'fecha_vencimiento' => $request->fecha_vencimiento ?? $producto->fecha_vencimiento,
            'descripcion' => $request->descripcion ?? $producto->descripcion,
            'fotos' => $request->has('fotos') ? $request->input('fotos') : $producto->fotos,
            'stock_actual' => $request->stock_actual ?? $producto->stock_actual,
            'stock_minimo' => $request->stock_minimo ?? $producto->stock_minimo,
            'precio_compra' => $request->precio_compra ?? $producto->precio_compra,
            'precio_venta' => $request->precio_venta ?? $producto->precio_venta,
            'impuesto' => $request->impuesto ?? $producto->impuesto,
            'estado' => $request->estado ?? $producto->estado,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Producto actualizado exitosamente',
            'data' => new ProductoResource($producto->load(['categoria', 'proveedor'])->loadCount(['lotes', 'ventaProductos', 'compraProductos']))
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified product from storage.
     * 
     * @param Producto $producto
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $producto = Producto::findOrFail($id);
            $producto->delete();

            $response = response()->json([
                'success' => true,
                'message' => 'Producto eliminado exitosamente'
            ], Response::HTTP_OK);

            // Agregar headers de seguridad y versión
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('X-API-Version', '2.0');

            return $response;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $response = response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);

            // Agregar headers de seguridad y versión
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('X-API-Version', '2.0');

            return $response;
        }
    }

    /**
     * Toggle product status
     * 
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function toggleEstado(Request $request, $id): JsonResponse
    {
        try {
            $producto = Producto::findOrFail($id);
            
            $nuevoEstado = $producto->estado === 'activo' ? 'inactivo' : 'activo';
            $producto->update(['estado' => $nuevoEstado]);

            $response = response()->json([
                'success' => true,
                'message' => 'Estado del producto actualizado exitosamente',
                'data' => new ProductoResource($producto->load(['categoria', 'proveedor']))
            ], Response::HTTP_OK);

            // Agregar headers de seguridad y versión
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('X-API-Version', '2.0');

            return $response;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $response = response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);

            // Agregar headers de seguridad y versión
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('X-API-Version', '2.0');

            return $response;
        }
    }

    /**
     * Get products with low stock.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function bajoStock(Request $request): JsonResponse
    {
        $query = Producto::with(['categoria'])
            ->whereColumn('stock_actual', '<=', 'stock_minimo');

        $perPage = min($request->per_page ?? 15, 100);
        $productos = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => new ProductoCollection($productos)
        ], Response::HTTP_OK);
    }

    /**
     * Get products expiring soon.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function proximosVencer(Request $request): JsonResponse
    {
        $dias = $request->dias_alerta ?? 30;
        
        $query = Producto::with(['categoria'])
            ->where('fecha_vencimiento', '<=', now()->addDays($dias))
            ->where('fecha_vencimiento', '>', now());

        $perPage = min($request->per_page ?? 15, 100);
        $productos = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => new ProductoCollection($productos)
        ], Response::HTTP_OK);
    }
}
