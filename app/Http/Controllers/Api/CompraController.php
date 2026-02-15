<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Compra\CompleteCompraAction;
use App\Actions\Compra\CreateCompraAction;
use App\Actions\Compra\DeleteCompraAction;
use App\Actions\Compra\ListComprasAction;
use App\Actions\Compra\UpdateCompraAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Compra\CompraCollection;
use App\Http\Resources\Compra\CompraResource;
use App\Models\Compra;
use Illuminate\Http\Request;

class CompraController extends Controller
{
    public function __construct(
        private CreateCompraAction $createCompraAction,
        private UpdateCompraAction $updateCompraAction,
        private DeleteCompraAction $deleteCompraAction,
        private ListComprasAction $listComprasAction,
        private CompleteCompraAction $completeCompraAction
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only([
            'search', 'estado', 'tipo_documento', 'numero_documento',
            'proveedor_id', 'usuario_id', 'caja_id',
            'fecha_inicio', 'fecha_fin', 'created_at_inicio', 'created_at_fin',
            'total_min', 'total_max', 'con_saldo',
            'sort', 'direction', 'per_page',
        ]);

        $compras = $this->listComprasAction->execute($filters);

        return $this->success(new CompraCollection($compras));
    }

    public function store(Request $request)
    {
        $compra = $this->createCompraAction->execute($request->all());

        return $this->created(
            new CompraResource($compra),
            'Compra creada exitosamente'
        );
    }

    public function show(Compra $compra)
    {
        return $this->success(
            new CompraResource($compra->load([
                'proveedor', 'usuario', 'caja', 'compraProductos.producto',
            ]))
        );
    }

    public function update(Request $request, Compra $compra)
    {
        $updatedCompra = $this->updateCompraAction->execute($compra, $request->all());

        return $this->success(
            new CompraResource($updatedCompra->load([
                'proveedor', 'usuario', 'caja', 'compraProductos.producto',
            ])),
            'Compra actualizada exitosamente'
        );
    }

    public function destroy(Compra $compra)
    {
        $this->deleteCompraAction->execute($compra);

        return $this->noContent('Compra eliminada exitosamente');
    }

    public function completar(Request $request, Compra $compra)
    {
        $completedCompra = $this->completeCompraAction->execute($compra, $request->all());

        return $this->success(
            new CompraResource($completedCompra->load([
                'proveedor', 'usuario', 'caja',
            ])),
            'Compra completada exitosamente'
        );
    }

    public function pendientes(Request $request)
    {
        $filters = array_merge($request->only(['per_page']), ['estado' => 'pendiente']);

        $compras = $this->listComprasAction->execute($filters);

        return $this->success(new CompraCollection($compras));
    }

    public function porFechaDocumento(Request $request)
    {
        $validated = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $filters = array_merge($validated, $request->only(['per_page']));

        $compras = $this->listComprasAction->execute($filters);

        return $this->success(new CompraCollection($compras));
    }
}
