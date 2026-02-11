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
    public function __construct(
        private CreateProductoAction $createProductoAction,
        private UpdateProductoAction $updateProductoAction,
        private DeleteProductoAction $deleteProductoAction,
        private ListProductosAction $listProductosAction,
        private BulkUpdateProductosAction $bulkUpdateProductosAction
    ) {}

    /**
     * Display a paginated listing of products with filtering.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search', 'estado', 'categoria_id', 'proveedor_id',
            'stock_bajo', 'proximos_vencer', 'precio_min', 'precio_max',
            'sort', 'direction', 'per_page'
        ]);
        
        $productos = $this->listProductosAction->execute($filters);

        return response()->json([
            'success' => true,
            'data' => new ProductoCollection($productos)
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created product in storage.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $producto = $this->createProductoAction->execute($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Producto creado exitosamente',
            'data' => new ProductoResource($producto->load(['categoria', 'proveedor']))
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified product with relationships.
     * 
     * @param Producto $producto
     * @return JsonResponse
     */
    public function show(Producto $producto): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new ProductoResource($producto->load(['categoria', 'proveedor']))
        ], Response::HTTP_OK);
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
        $updatedProducto = $this->updateProductoAction->execute($producto, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Producto actualizado exitosamente',
            'data' => new ProductoResource($updatedProducto->load(['categoria', 'proveedor']))
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified product from storage.
     * 
     * @param Producto $producto
     * @return JsonResponse
     */
    public function destroy(Producto $producto): JsonResponse
    {
        $this->deleteProductoAction->execute($producto);

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado exitosamente'
        ], Response::HTTP_OK);
    }

    /**
     * Bulk update products status or stock.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'string', 'exists:productos,id'],
            'estado' => ['sometimes', 'in:activo,inactivo,descontinuado'],
            'stock_actual' => ['sometimes', 'integer', 'min:0'],
            'precio_venta' => ['sometimes', 'numeric', 'min:0'],
        ]);

        $updatedCount = $this->bulkUpdateProductosAction->execute($validated['ids'], $validated);

        return response()->json([
            'success' => true,
            'message' => 'Productos actualizados exitosamente',
            'data' => [
                'updated_count' => $updatedCount
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Get products with low stock.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function bajoStock(Request $request): JsonResponse
    {
        $filters = array_merge($request->only(['per_page']), ['stock_bajo' => true]);
        
        $productos = $this->listProductosAction->execute($filters);

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
        $filters = array_merge($request->only(['per_page']), ['proximos_vencer' => true]);
        
        $productos = $this->listProductosAction->execute($filters);

        return response()->json([
            'success' => true,
            'data' => new ProductoCollection($productos)
        ], Response::HTTP_OK);
    }
}
