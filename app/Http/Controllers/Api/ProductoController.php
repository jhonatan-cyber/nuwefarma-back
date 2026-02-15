<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Product\BulkUpdateProductosAction;
use App\Actions\Product\CreateProductoAction;
use App\Actions\Product\DeleteProductoAction;
use App\Actions\Product\ListProductosAction;
use App\Actions\Product\UpdateProductoAction;
use App\DTOs\Product\CreateProductoDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Producto\StoreProductoRequest;
use App\Http\Requests\Producto\UpdateProductoRequest;
use App\Http\Resources\Producto\ProductoCollection;
use App\Http\Resources\Producto\ProductoResource;
use App\Models\Producto;
use Illuminate\Http\Request;

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
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'search', 'estado', 'categoria_id', 'proveedor_id',
            'stock_bajo', 'proximos_vencer', 'precio_min', 'precio_max',
            'sort', 'direction', 'per_page',
        ]);

        $productos = $this->listProductosAction->execute($filters);

        return $this->success(new ProductoCollection($productos));
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(StoreProductoRequest $request)
    {
        $dto = CreateProductoDTO::fromArray($request->validated());
        $producto = $this->createProductoAction->execute($dto);

        return $this->created(
            new ProductoResource($producto->load(['categoria', 'proveedor'])),
            'Producto creado exitosamente'
        );
    }

    /**
     * Display the specified product with relationships.
     */
    public function show(Producto $producto)
    {
        return $this->success(
            new ProductoResource($producto->load(['categoria', 'proveedor']))
        );
    }

    /**
     * Update the specified product in storage.
     */
    public function update(UpdateProductoRequest $request, Producto $producto)
    {
        $updatedProducto = $this->updateProductoAction->execute($producto, $request->validated());

        return $this->success(
            new ProductoResource($updatedProducto->load(['categoria', 'proveedor'])),
            'Producto actualizado exitosamente'
        );
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Producto $producto)
    {
        $this->deleteProductoAction->execute($producto);

        return $this->noContent('Producto eliminado exitosamente');
    }

    /**
     * Bulk update products status or stock.
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'string', 'exists:productos,id'],
            'estado' => ['sometimes', 'in:activo,inactivo,descontinuado'],
            'stock_actual' => ['sometimes', 'integer', 'min:0'],
            'precio_venta' => ['sometimes', 'numeric', 'min:0'],
        ]);

        $updatedCount = $this->bulkUpdateProductosAction->execute($validated['ids'], $validated);

        return $this->success([
            'updated_count' => $updatedCount,
        ], 'Productos actualizados exitosamente');
    }

    /**
     * Get products with low stock.
     */
    public function bajoStock(Request $request)
    {
        $filters = array_merge($request->only(['per_page']), ['stock_bajo' => true]);

        $productos = $this->listProductosAction->execute($filters);

        return $this->success(new ProductoCollection($productos));
    }

    /**
     * Get products expiring soon.
     */
    public function proximosVencer(Request $request)
    {
        $filters = array_merge($request->only(['per_page']), ['proximos_vencer' => true]);

        $productos = $this->listProductosAction->execute($filters);

        return $this->success(new ProductoCollection($productos));
    }
}
