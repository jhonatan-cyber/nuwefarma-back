<?php

declare(strict_types=1);

namespace App\Actions\Venta;

use App\Models\Venta;
use Illuminate\Pagination\LengthAwarePaginator;

class ListVentasAction
{
    /**
     * Get a paginated list of sales with filtering.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<Venta>
     */
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $query = Venta::with(['cliente', 'usuario', 'caja'])
            ->withSum('salidasInventario as costo_venta', 'costo_total');

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
        $query->when(! empty($filters['search']), function ($q) use ($filters) {
            $search = $filters['search'];
            $q->whereHas('cliente', function ($subQuery) use ($search) {
                $subQuery->where('nombre', 'like', "%{$search}%")
                    ->orWhere('apellidos', 'like', "%{$search}%")
                    ->orWhere('ci', 'like', "%{$search}%");
            });
        });

        $query->when(! empty($filters['estado']), function ($q) use ($filters) {
            $q->where('estado', $filters['estado']);
        });

        $query->when(! empty($filters['tipo_pago']), function ($q) use ($filters) {
            $q->where('tipo_pago', $filters['tipo_pago']);
        });

        $query->when(! empty($filters['metodo_pago']), function ($q) use ($filters) {
            $q->where('metodo_pago', $filters['metodo_pago']);
        });

        $query->when(! empty($filters['cliente_id']), function ($q) use ($filters) {
            $q->where('cliente_id', $filters['cliente_id']);
        });

        $query->when(! empty($filters['usuario_id']), function ($q) use ($filters) {
            $q->where('usuario_id', $filters['usuario_id']);
        });

        $query->when(! empty($filters['caja_id']), function ($q) use ($filters) {
            $q->where('caja_id', $filters['caja_id']);
        });

        // Date range filter
        $query->when(! empty($filters['fecha_inicio']), function ($q) use ($filters) {
            $q->whereDate('created_at', '>=', $filters['fecha_inicio']);
        });

        $query->when(! empty($filters['fecha_fin']), function ($q) use ($filters) {
            $q->whereDate('created_at', '<=', $filters['fecha_fin']);
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
