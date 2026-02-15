<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Caja\AbrirCajaAction;
use App\Actions\Caja\CerrarCajaAction;
use App\Actions\Caja\CreateCajaAction;
use App\Actions\Caja\DeleteCajaAction;
use App\Actions\Caja\ListCajasAction;
use App\Actions\Caja\UpdateCajaAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Caja\StoreCajaRequest;
use App\Http\Resources\Caja\CajaCollection;
use App\Http\Resources\Caja\CajaResource;
use App\Models\Caja;
use Illuminate\Http\Request;

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

    public function index(Request $request)
    {
        $filters = $request->only([
            'search', 'estado', 'sucursal_id', 'gerente_id',
            'saldo_min', 'saldo_max', 'abiertas',
            'sort', 'direction', 'per_page',
        ]);

        $cajas = $this->listCajasAction->execute($filters);

        return $this->success(new CajaCollection($cajas));
    }

    public function store(StoreCajaRequest $request)
    {
        $caja = $this->createCajaAction->execute($request->validated());

        return $this->created(
            new CajaResource($caja->load(['sucursal', 'gerente'])),
            'Caja creada exitosamente'
        );
    }

    public function show(Caja $caja)
    {
        return $this->success(
            new CajaResource($caja->load(['sucursal', 'gerente']))
        );
    }

    public function update(Request $request, Caja $caja)
    {
        $updatedCaja = $this->updateCajaAction->execute($caja, $request->all());

        return $this->success(
            new CajaResource($updatedCaja->load(['sucursal', 'gerente'])),
            'Caja actualizada exitosamente'
        );
    }

    public function destroy(Caja $caja)
    {
        $this->deleteCajaAction->execute($caja);

        return $this->noContent('Caja eliminada exitosamente');
    }

    public function abrir(Request $request, Caja $caja)
    {
        $openedCaja = $this->abrirCajaAction->execute($caja, $request->all());

        return $this->success(
            new CajaResource($openedCaja->load(['sucursal', 'gerente'])),
            'Caja abierta exitosamente'
        );
    }

    public function cerrar(Request $request, Caja $caja)
    {
        $closedCaja = $this->cerrarCajaAction->execute($caja, $request->all());

        return $this->success(
            new CajaResource($closedCaja->load(['sucursal', 'gerente'])),
            'Caja cerrada exitosamente'
        );
    }

    public function abiertas(Request $request)
    {
        $filters = array_merge($request->only(['per_page']), ['abiertas' => true]);

        $cajas = $this->listCajasAction->execute($filters);

        return $this->success(new CajaCollection($cajas));
    }

    public function cerradas(Request $request)
    {
        $filters = array_merge($request->only(['per_page']), ['abiertas' => false]);

        $cajas = $this->listCajasAction->execute($filters);

        return $this->success(new CajaCollection($cajas));
    }
}
