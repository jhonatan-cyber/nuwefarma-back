<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Caja\CajaCollection;
use App\Http\Resources\Caja\CajaResource;
use App\Models\Caja;
use App\Actions\Caja\CreateCajaAction;
use App\Actions\Caja\UpdateCajaAction;
use App\Actions\Caja\DeleteCajaAction;
use App\Actions\Caja\ListCajasAction;
use App\Actions\Caja\AbrirCajaAction;
use App\Actions\Caja\CerrarCajaAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CajaController extends Controller
{
    public function __construct(
        private CreateCajaAction $createCajaAction,
        private UpdateCajaAction $updateCajaAction,
        private DeleteCajaAction $deleteCajaAction,
        private ListCajasAction $listCajasAction,
        private AbrirCajaAction $abrirCajaAction,
        private CerrarCajaAction $cerrarCajaAction
    ) {}

    /**
     * Display a paginated listing of cash registers with filtering.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search', 'estado', 'sucursal_id', 'gerente_id',
            'saldo_min', 'saldo_max', 'abiertas',
            'sort', 'direction', 'per_page'
        ]);
        
        $cajas = $this->listCajasAction->execute($filters);

        return response()->json([
            'success' => true,
            'data' => new CajaCollection($cajas)
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created cash register in storage.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $caja = $this->createCajaAction->execute($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Caja creada exitosamente',
            'data' => new CajaResource($caja->load(['sucursal', 'gerente']))
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified cash register with relationships.
     * 
     * @param Caja $caja
     * @return JsonResponse
     */
    public function show(Caja $caja): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new CajaResource($caja->load(['sucursal', 'gerente']))
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified cash register in storage.
     * 
     * @param Request $request
     * @param Caja $caja
     * @return JsonResponse
     */
    public function update(Request $request, Caja $caja): JsonResponse
    {
        $updatedCaja = $this->updateCajaAction->execute($caja, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Caja actualizada exitosamente',
            'data' => new CajaResource($updatedCaja->load(['sucursal', 'gerente']))
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified cash register from storage.
     * 
     * @param Caja $caja
     * @return JsonResponse
     */
    public function destroy(Caja $caja): JsonResponse
    {
        $this->deleteCajaAction->execute($caja);

        return response()->json([
            'success' => true,
            'message' => 'Caja eliminada exitosamente'
        ], Response::HTTP_OK);
    }

    /**
     * Open a cash register.
     * 
     * @param Request $request
     * @param Caja $caja
     * @return JsonResponse
     */
    public function abrir(Request $request, Caja $caja): JsonResponse
    {
        $openedCaja = $this->abrirCajaAction->execute($caja, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Caja abierta exitosamente',
            'data' => new CajaResource($openedCaja->load(['sucursal', 'gerente']))
        ], Response::HTTP_OK);
    }

    /**
     * Close a cash register.
     * 
     * @param Request $request
     * @param Caja $caja
     * @return JsonResponse
     */
    public function cerrar(Request $request, Caja $caja): JsonResponse
    {
        $closedCaja = $this->cerrarCajaAction->execute($caja, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Caja cerrada exitosamente',
            'data' => new CajaResource($closedCaja->load(['sucursal', 'gerente']))
        ], Response::HTTP_OK);
    }

    /**
     * Get open cash registers.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function abiertas(Request $request): JsonResponse
    {
        $filters = array_merge($request->only(['per_page']), ['abiertas' => true]);
        
        $cajas = $this->listCajasAction->execute($filters);

        return response()->json([
            'success' => true,
            'data' => new CajaCollection($cajas)
        ], Response::HTTP_OK);
    }

    /**
     * Get closed cash registers.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function cerradas(Request $request): JsonResponse
    {
        $filters = array_merge($request->only(['per_page']), ['abiertas' => false]);
        
        $cajas = $this->listCajasAction->execute($filters);

        return response()->json([
            'success' => true,
            'data' => new CajaCollection($cajas)
        ], Response::HTTP_OK);
    }
}
