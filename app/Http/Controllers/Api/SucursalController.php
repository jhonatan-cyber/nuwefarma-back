<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Sucursal\BulkUpdateSucursalesAction;
use App\Actions\Sucursal\CreateSucursalAction;
use App\Actions\Sucursal\DeleteSucursalAction;
use App\Actions\Sucursal\ListSucursalesAction;
use App\Actions\Sucursal\UpdateSucursalAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sucursal\StoreSucursalRequest;
use App\Http\Requests\Sucursal\UpdateSucursalRequest;
use App\Http\Resources\Sucursal\SucursalCollection;
use App\Http\Resources\Sucursal\SucursalResource;
use App\Models\Sucursal;
use Illuminate\Http\Request;

class SucursalController extends Controller
{
    public function __construct(
        private CreateSucursalAction $createSucursalAction,
        private UpdateSucursalAction $updateSucursalAction,
        private DeleteSucursalAction $deleteSucursalAction,
        private ListSucursalesAction $listSucursalesAction,
        private BulkUpdateSucursalesAction $bulkUpdateSucursalesAction
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only([
            'search', 'estado', 'ciudad', 'departamento', 'pais',
            'gerente_id', 'capacidad_min', 'capacidad_max',
            'sort', 'direction', 'per_page',
        ]);

        $sucursales = $this->listSucursalesAction->execute($filters);

        return $this->success(new SucursalCollection($sucursales));
    }

    public function store(StoreSucursalRequest $request)
    {
        $sucursal = $this->createSucursalAction->execute($request->validated());

        return $this->created(
            new SucursalResource($sucursal->load('gerente')),
            'Sucursal creada exitosamente'
        );
    }

    public function show(Sucursal $sucursal)
    {
        return $this->success(
            new SucursalResource($sucursal->loadCount(['usuarios', 'cajas']))
        );
    }

    public function update(UpdateSucursalRequest $request, Sucursal $sucursal)
    {
        $updatedSucursal = $this->updateSucursalAction->execute($sucursal, $request->validated());

        return $this->success(
            new SucursalResource($updatedSucursal->loadCount(['usuarios', 'cajas'])),
            'Sucursal actualizada exitosamente'
        );
    }

    public function destroy(Sucursal $sucursal)
    {
        $this->deleteSucursalAction->execute($sucursal);

        return $this->noContent('Sucursal eliminada exitosamente');
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'string', 'exists:sucursals,id'],
            'estado' => ['required', 'in:activo,inactivo'],
        ]);

        $updatedCount = $this->bulkUpdateSucursalesAction->execute($validated['ids'], $validated['estado']);

        return $this->success([
            'updated_count' => $updatedCount,
        ], 'Sucursales actualizadas exitosamente');
    }

    public function activas(Request $request)
    {
        $filters = array_merge($request->only(['per_page']), ['estado' => 'activo']);

        $sucursales = $this->listSucursalesAction->execute($filters);

        return $this->success(new SucursalCollection($sucursales));
    }
}
