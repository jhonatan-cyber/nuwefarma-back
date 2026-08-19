<?php

declare(strict_types=1);

namespace App\Actions\Compra;

use App\Models\Compra;
use Illuminate\Pagination\LengthAwarePaginator;

class ListComprasAction
{
    /**
     * Get a paginated list of purchases with filtering.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<Compra>
     */
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $query = Compra::with(['proveedor', 'usuario', 'sucursal', 'productos.producto']);

        $this->applyFilters($query, $filters);

        $this->applySorting($query, $filters);

        $perPage = min($filters['per_page'] ?? 15, 100);

        return $query->paginate($perPage);
    }

    /**
     * Apply filters to the query.
     *
     * @param  mixed  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters($query, array $filters): void
    {
        $termino = $filters['q'] ?? ($filters['search'] ?? null);

        $query->when(! empty($termino), function ($q) use ($termino) {
            $q->where(function ($sub) use ($termino) {
                $sub->where('numero_compra', 'like', "%{$termino}%")
                    ->orWhere('numero_documento', 'like', "%{$termino}%")
                    ->orWhereHas('proveedor', function ($proveedorQuery) use ($termino) {
                        $proveedorQuery->where('nombre', 'like', "%{$termino}%")
                            ->orWhere('nit', 'like', "%{$termino}%")
                            ->orWhere('ruc', 'like', "%{$termino}%");
                    });
            });
        });

        $query->when(! empty($filters['estado']), function ($q) use ($filters) {
            $q->where('estado', $filters['estado']);
        });

        $query->when(! empty($filters['tipo_documento']), function ($q) use ($filters) {
            $q->where('tipo_documento', $filters['tipo_documento']);
        });

        $query->when(! empty($filters['numero_documento']), function ($q) use ($filters) {
            $q->where('numero_documento', 'like', "%{$filters['numero_documento']}%");
        });

        $query->when(! empty($filters['proveedor_id']), function ($q) use ($filters) {
            $q->where('proveedor_id', $filters['proveedor_id']);
        });

        $query->when(! empty($filters['usuario_id']), function ($q) use ($filters) {
            $q->where('usuario_id', $filters['usuario_id']);
        });

        $query->when(! empty($filters['sucursal_id']), function ($q) use ($filters) {
            $q->where('sucursal_id', $filters['sucursal_id']);
        });

        $query->when(! empty($filters['metodo_pago']), function ($q) use ($filters) {
            $q->where('metodo_pago', $filters['metodo_pago']);
        });

        $query->when(! empty($filters['caja_id']), function ($q) use ($filters) {
            $q->where('caja_id', $filters['caja_id']);
        });

        // Date range filter
        $query->when(! empty($filters['fecha_inicio']), function ($q) use ($filters) {
            $q->whereDate('fecha_documento', '>=', $filters['fecha_inicio']);
        });

        $query->when(! empty($filters['fecha_fin']), function ($q) use ($filters) {
            $q->whereDate('fecha_documento', '<=', $filters['fecha_fin']);
        });

        // Date range filter for created_at
        $query->when(! empty($filters['created_at_inicio']), function ($q) use ($filters) {
            $q->whereDate('created_at', '>=', $filters['created_at_inicio']);
        });

        $query->when(! empty($filters['created_at_fin']), function ($q) use ($filters) {
            $q->whereDate('created_at', '<=', $filters['created_at_fin']);
        });

        // Amount range filter
        $query->when(isset($filters['total_min']), function ($q) use ($filters) {
            $q->where('total', '>=', $filters['total_min']);
        });

        $query->when(isset($filters['total_max']), function ($q) use ($filters) {
            $q->where('total', '<=', $filters['total_max']);
        });

        // Filter by pending balance
        $query->when(isset($filters['con_saldo']), function ($q) use ($filters) {
            if ($filters['con_saldo']) {
                $q->where('saldo_pendiente', '>', 0);
            }
        });
    }

    /**
     * Apply sorting to the query.
     *
     * @param  mixed  $query
     * @param  array<string, mixed>  $filters
     */
    private function applySorting($query, array $filters): void
    {
        $sort = $filters['sort'] ?? 'created_at';
        $direction = $filters['direction'] ?? 'desc';

        $query->orderBy($sort, $direction);
    }
}