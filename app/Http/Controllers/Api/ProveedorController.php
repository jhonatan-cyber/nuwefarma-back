<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Proveedor\BulkUpdateProveedoresAction;
use App\Actions\Proveedor\CreateProveedorAction;
use App\Actions\Proveedor\DeleteProveedorAction;
use App\Actions\Proveedor\ListProveedoresAction;
use App\Actions\Proveedor\UpdateProveedorAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Proveedor\StoreProveedorRequest;
use App\Http\Resources\Proveedor\ProveedorCollection;
use App\Http\Resources\Proveedor\ProveedorResource;
use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    public function __construct(
        private CreateProveedorAction $createProveedorAction,
        private UpdateProveedorAction $updateProveedorAction,
        private DeleteProveedorAction $deleteProveedorAction,
        private ListProveedoresAction $listProveedoresAction,
        private BulkUpdateProveedoresAction $bulkUpdateProveedoresAction
    ) {}

    /**
     * Display a paginated listing of providers with filtering.
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'search', 'estado', 'ruc', 'sort', 'direction', 'per_page',
        ]);

        $proveedores = $this->listProveedoresAction->execute($filters);

        return $this->success(new ProveedorCollection($proveedores));
    }

    /**
     * Store a newly created provider in storage.
     */
    public function store(StoreProveedorRequest $request)
    {
        $proveedor = $this->createProveedorAction->execute($request->validated());

        return $this->created(
            new ProveedorResource($proveedor),
            'Proveedor creado exitosamente'
        );
    }

    /**
     * Display the specified provider with relationships.
     */
    public function show(Proveedor $proveedor)
    {
        return $this->success(
            new ProveedorResource($proveedor->loadCount('productos'))
        );
    }

    /**
     * Update the specified provider in storage.
     */
    public function update(Request $request, Proveedor $proveedor)
    {
        $updatedProveedor = $this->updateProveedorAction->execute($proveedor, $request->all());

        return $this->success(
            new ProveedorResource($updatedProveedor->loadCount('productos')),
            'Proveedor actualizado exitosamente'
        );
    }

    /**
     * Remove the specified provider from storage.
     */
    public function destroy(Proveedor $proveedor)
    {
        $this->deleteProveedorAction->execute($proveedor);

        return $this->noContent('Proveedor eliminado exitosamente');
    }

    /**
     * Bulk update providers status.
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'string', 'exists:proveedors,id'],
            'estado' => ['required', 'in:activo,inactivo'],
        ]);

        $updatedCount = $this->bulkUpdateProveedoresAction->execute($validated['ids'], $validated['estado']);

        return $this->success([
            'updated_count' => $updatedCount,
        ], 'Proveedores actualizados exitosamente');
    }
}
