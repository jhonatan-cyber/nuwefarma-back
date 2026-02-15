<?php

declare(strict_types=1);

namespace App\Actions\Categoria;

use App\DTOs\Categoria\ListCategoriasDTO;
use App\Models\Categoria;
use Illuminate\Pagination\LengthAwarePaginator;

class ListCategoriasAction
{
    /**
     * Get a paginated list of categories with filtering.
     *
     * @return LengthAwarePaginator<Categoria>
     */
    public function execute(ListCategoriasDTO $filters): LengthAwarePaginator
    {
        $query = Categoria::withCount('productos');

        $this->applyFilters($query, $filters);

        $this->applySorting($query, $filters);

        $perPage = min($filters->per_page ?? 15, 100);

        return $query->paginate($perPage);
    }

    /**
     * Apply filters to the query.
     *
     * @param  mixed  $query
     */
    private function applyFilters($query, ListCategoriasDTO $filters): void
    {
        $query->when($filters->search, function ($q) use ($filters) {
            $search = $filters->search;
            $q->where('nombre', 'like', "%{$search}%")
                ->orWhere('descripcion', 'like', "%{$search}%");
        });

        $query->when($filters->estado, function ($q) use ($filters) {
            $q->where('estado', $filters->estado);
        });
    }

    /**
     * Apply sorting to the query.
     *
     * @param  mixed  $query
     */
    private function applySorting($query, ListCategoriasDTO $filters): void
    {
        $sort = $filters->sort ?? 'nombre';
        $direction = $filters->direction ?? 'asc';

        $query->orderBy($sort, $direction);
    }
}
