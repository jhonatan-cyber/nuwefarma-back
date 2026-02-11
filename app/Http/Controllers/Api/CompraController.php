<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Compra\CompraCollection;
use App\Http\Resources\Compra\CompraResource;
use App\Models\Compra;
use App\Actions\Compra\CreateCompraAction;
use App\Actions\Compra\UpdateCompraAction;
use App\Actions\Compra\DeleteCompraAction;
use App\Actions\Compra\ListComprasAction;
use App\Actions\Compra\CompleteCompraAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CompraController extends Controller
{
    public function __construct(
        private CreateCompraAction $createCompraAction,
        private UpdateCompraAction $updateCompraAction,
        private DeleteCompraAction $deleteCompraAction,
        private ListComprasAction $listComprasAction,
        private CompleteCompraAction $completeCompraAction
    ) {}

    /**
     * Display a paginated listing of purchases with filtering.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search', 'estado', 'tipo_documento', 'numero_documento',
            'proveedor_id', 'usuario_id', 'caja_id',
            'fecha_inicio', 'fecha_fin', 'created_at_inicio', 'created_at_fin',
            'total_min', 'total_max', 'con_saldo',
            'sort', 'direction', 'per_page'
        ]);
        
        $compras = $this->listComprasAction->execute($filters);

        return response()->json([
            'success' => true,
            'data' => new CompraCollection($compras)
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created purchase in storage.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $compra = $this->createCompraAction->execute($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Compra creada exitosamente',
            'data' => new CompraResource($compra)
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified purchase with relationships.
     * 
     * @param Compra $compra
     * @return JsonResponse
     */
    public function show(Compra $compra): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new CompraResource($compra->load([
                'proveedor', 'usuario', 'caja', 'compraProductos.producto'
            ]))
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified purchase in storage.
     * 
     * @param Request $request
     * @param Compra $compra
     * @return JsonResponse
     */
    public function update(Request $request, Compra $compra): JsonResponse
    {
        $updatedCompra = $this->updateCompraAction->execute($compra, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Compra actualizada exitosamente',
            'data' => new CompraResource($updatedCompra->load([
                'proveedor', 'usuario', 'caja', 'compraProductos.producto'
            ]))
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified purchase from storage.
     * 
     * @param Compra $compra
     * @return JsonResponse
     */
    public function destroy(Compra $compra): JsonResponse
    {
        $this->deleteCompraAction->execute($compra);

        return response()->json([
            'success' => true,
            'message' => 'Compra eliminada exitosamente'
        ], Response::HTTP_OK);
    }

    /**
     * Complete a pending purchase.
     * 
     * @param Request $request
     * @param Compra $compra
     * @return JsonResponse
     */
    public function completar(Request $request, Compra $compra): JsonResponse
    {
        $completedCompra = $this->completeCompraAction->execute($compra, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Compra completada exitosamente',
            'data' => new CompraResource($completedCompra->load([
                'proveedor', 'usuario', 'caja'
            ]))
        ], Response::HTTP_OK);
    }

    /**
     * Get purchases with pending balance.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function pendientes(Request $request): JsonResponse
    {
        $filters = array_merge($request->only(['per_page']), ['estado' => 'pendiente']);
        
        $compras = $this->listComprasAction->execute($filters);

        return response()->json([
            'success' => true,
            'data' => new CompraCollection($compras)
        ], Response::HTTP_OK);
    }

    /**
     * Get purchases by document date range.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function porFechaDocumento(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $filters = array_merge($validated, $request->only(['per_page']));
        
        $compras = $this->listComprasAction->execute($filters);

        return response()->json([
            'success' => true,
            'data' => new CompraCollection($compras)
        ], Response::HTTP_OK);
    }
}
