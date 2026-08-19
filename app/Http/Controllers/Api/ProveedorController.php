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
use App\Http\Requests\Proveedor\UpdateProveedorRequest;
use App\Http\Resources\Proveedor\ProveedorCollection;
use App\Http\Resources\Proveedor\ProveedorResource;
use App\Models\Compra;
use App\Models\Proveedor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'search', 'estado', 'nit', 'ruc', 'sort', 'direction', 'per_page',
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
            new ProveedorResource($proveedor->loadCount(['productos', 'compras']))
        );
    }

    /**
     * Update the specified provider in storage.
     */
    public function update(UpdateProveedorRequest $request, Proveedor $proveedor)
    {
        $updatedProveedor = $this->updateProveedorAction->execute($proveedor, $request->validated());

        return $this->success(
            new ProveedorResource($updatedProveedor->loadCount(['productos', 'compras'])),
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
            'ids.*' => ['required', 'string', 'exists:proveedores,id'],
            'estado' => ['required', 'in:activo,inactivo'],
        ]);

        $updatedCount = $this->bulkUpdateProveedoresAction->execute($validated['ids'], $validated['estado']);

        return $this->success([
            'updated_count' => $updatedCount,
        ], 'Proveedores actualizados exitosamente');
    }

    /**
     * Toggle a provider status.
     */
    public function toggleEstado(Proveedor $proveedor): JsonResponse
    {
        $nuevoEstado = $proveedor->estado === 'activo' ? 'inactivo' : 'activo';
        $proveedor->update(['estado' => $nuevoEstado]);

        return $this->success(
            new ProveedorResource($proveedor->loadCount(['productos', 'compras'])),
            "Proveedor {$nuevoEstado} exitosamente"
        );
    }

    /**
     * Providers statistics overview.
     */
    public function statsOverview(Request $request): JsonResponse
    {
        $base = Proveedor::query();

        if ($request->query('estado')) {
            $base->where('estado', $request->query('estado'));
        }

        $total = (clone $base)->count();
        $activos = (clone $base)->where('estado', 'activo')->count();
        $inactivos = (clone $base)->where('estado', 'inactivo')->count();
        $conProductos = (clone $base)->has('productos')->count();

        $conteos = (clone $base)
            ->withCount('productos')
            ->withCount(['compras' => fn ($q) => $q->where('estado', 'recibida')])
            ->get()
            ->mapWithKeys(function (Proveedor $proveedor) {
                return [$proveedor->id => [
                    'productos' => $proveedor->productos_count,
                    'compras_recibidas' => $proveedor->compras_count,
                ]];
            });

        $montoComprasRecibidas = (float) ((clone $base)
            ->leftJoin('compras', 'compras.proveedor_id', '=', 'proveedores.id')
            ->where('compras.estado', 'recibida')
            ->select(DB::raw('COALESCE(SUM(compras.total), 0) as s'))
            ->value('s'));

        $porCategoria = (clone $base)
            ->whereNotNull('categoria')
            ->select(['categoria', DB::raw('COUNT(*) as cantidad')])
            ->groupBy('categoria')
            ->orderByDesc('cantidad')
            ->limit(10)
            ->get();

        return $this->success([
            'resumen' => [
                'total' => $total,
                'activos' => $activos,
                'inactivos' => $inactivos,
                'con_productos' => $conProductos,
            ],
            'compras' => [
                'monto_recibido' => round($montoComprasRecibidas, 2),
            ],
            'conteos' => $conteos,
            'por_categoria' => $porCategoria,
        ]);
    }

    /**
     * Estado de cuenta del proveedor: compras por pagar, pagos realizados y saldo.
     */
    public function estadoCuenta(Proveedor $proveedor)
    {
        $compras = Compra::with(['pagos'])
            ->where('proveedor_id', $proveedor->id)
            ->whereNotIn('estado', ['cancelada', 'devuelta'])
            ->orderByDesc('fecha_compra')
            ->get();

        $porPagar = $compras
            ->filter(fn (Compra $compra) => (float) $compra->saldo_pendiente > 0)
            ->values();

        $pagos = $compras
            ->flatMap(fn (Compra $compra) => $compra->pagos)
            ->sortByDesc('fecha_pago')
            ->values();

        return $this->success([
            'proveedor' => [
                'id' => $proveedor->id,
                'nombre' => $proveedor->nombre,
                'nit' => $proveedor->nit,
                'email' => $proveedor->email,
                'telefono' => $proveedor->telefono,
            ],
            'resumen' => [
                'total_compras' => round((float) $compras->where('estado', 'recibida')->sum('total'), 2),
                'total_pagado' => round((float) $compras->sum('pagado'), 2),
                'saldo_pendiente' => round((float) $compras->sum('saldo_pendiente'), 2),
                'cantidad_cuentas_abiertas' => $porPagar->count(),
            ],
            'cuentas_por_pagar' => $porPagar->map(fn (Compra $compra) => [
                'id' => $compra->id,
                'documento_numero' => $compra->numero_compra,
                'fecha' => $compra->fecha_compra,
                'fecha_vencimiento' => $compra->fecha_vencimiento,
                'total' => $compra->total,
                'pagado' => $compra->pagado,
                'saldo_pendiente' => $compra->saldo_pendiente,
                'estado' => $compra->estado,
            ]),
            'pagos' => $pagos->map(fn ($pago) => [
                'id' => $pago->id,
                'numero_pago' => $pago->numero_pago,
                'documento_numero' => $pago->documento_numero,
                'fecha_pago' => $pago->fecha_pago,
                'monto' => $pago->monto,
                'metodo_pago' => $pago->metodo_pago,
                'tipo_pago' => $pago->tipo_pago,
            ]),
        ], 'Estado de cuenta del proveedor');
    }
}
