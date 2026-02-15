<?php

namespace App\Repositories;

use App\DTOs\Product\CreateProductoDTO;
use App\Enums\EstadoEnum;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ProductoRepository
{
    public function __construct(
        private readonly Producto $model
    ) {}

    public function findById(string $id): ?Producto
    {
        return Cache::remember(
            "producto_{$id}",
            now()->addHours(2),
            fn () => $this->model->with(['categoria:id,nombre', 'proveedor:id,nombre'])->find($id)
        );
    }

    public function findByUuid(string $uuid): ?Producto
    {
        return $this->model->whereUuid($uuid)->first();
    }

    public function getAll(): Collection
    {
        return Cache::remember(
            'productos_all',
            now()->addMinutes(30),
            fn () => $this->model->with(['categoria:id,nombre', 'proveedor:id,nombre'])->get()
        );
    }

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->with(['categoria:id,nombre', 'proveedor:id,nombre']);

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    public function create(CreateProductoDTO $dto): Producto
    {
        $data = $dto->toArray();

        // Ensure required fields have default values
        $data['permite_fraccionar'] = $data['permite_fraccionar'] ?? false;
        $data['refrigeracion_requerida'] = $data['refrigeracion_requerida'] ?? false;
        $data['dias_para_alertar_vencimiento'] = $data['dias_para_alertar_vencimiento'] ?? 60;

        $producto = $this->model->create($data);

        // Clear cache
        $this->clearCache();

        return $producto;
    }

    public function update(string $id, array $data): bool
    {
        $result = $this->model->where('id', $id)->update($data);

        if ($result) {
            $this->clearCache();
            Cache::forget("producto_{$id}");
        }

        return $result;
    }

    public function delete(string $id): bool
    {
        $result = $this->model->where('id', $id)->delete();

        if ($result) {
            $this->clearCache();
            Cache::forget("producto_{$id}");
        }

        return $result;
    }

    public function activos(): Collection
    {
        return Cache::remember(
            'productos_activos',
            now()->addMinutes(15),
            fn () => $this->model->activos()->with(['categoria:id,nombre', 'proveedor:id,nombre'])->get()
        );
    }

    public function inactivos(): Collection
    {
        return Cache::remember(
            'productos_inactivos',
            now()->addMinutes(15),
            fn () => $this->model->inactivos()->with(['categoria:id,nombre', 'proveedor:id,nombre'])->get()
        );
    }

    public function bajoStock(): Collection
    {
        return Cache::remember(
            'productos_bajo_stock',
            now()->addMinutes(10),
            fn () => $this->model->bajoStock()->activos()->with(['categoria:id,nombre', 'proveedor:id,nombre'])->get()
        );
    }

    public function proximosAVencer(int $dias = 60): Collection
    {
        return Cache::remember(
            "productos_proximos_vencer_{$dias}",
            now()->addMinutes(10),
            fn () => $this->model->proximosAVencer($dias)->activos()->with(['categoria:id,nombre', 'proveedor:id,nombre'])->get()
        );
    }

    public function sinStock(): Collection
    {
        return Cache::remember(
            'productos_sin_stock',
            now()->addMinutes(10),
            fn () => $this->model->sinStock()->activos()->with(['categoria:id,nombre', 'proveedor:id,nombre'])->get()
        );
    }

    public function porCategoria(string $categoriaId): Collection
    {
        return Cache::remember(
            "productos_categoria_{$categoriaId}",
            now()->addMinutes(30),
            fn () => $this->model->porCategoria($categoriaId)->activos()->with(['categoria:id,nombre', 'proveedor:id,nombre'])->get()
        );
    }

    public function porProveedor(string $proveedorId): Collection
    {
        return Cache::remember(
            "productos_proveedor_{$proveedorId}",
            now()->addMinutes(30),
            fn () => $this->model->porProveedor($proveedorId)->activos()->with(['categoria:id,nombre', 'proveedor:id,nombre'])->get()
        );
    }

    public function search(string $termino, array $filtros = []): Collection
    {
        $query = $this->model->busqueda($termino)->activos();

        $this->applyFilters($query, $filtros);

        return $query->with(['categoria:id,nombre', 'proveedor:id,nombre'])->limit(50)->get();
    }

    public function conPrecioEntre(float $min, float $max): Collection
    {
        return Cache::remember(
            "productos_precio_{$min}_{$max}",
            now()->addMinutes(20),
            fn () => $this->model->conPrecioEntre($min, $max)->activos()->with(['categoria:id,nombre', 'proveedor:id,nombre'])->get()
        );
    }

    public function vencidos(): Collection
    {
        return Cache::remember(
            'productos_vencidos',
            now()->addMinutes(15),
            fn () => $this->model->vencidos()->with(['categoria:id,nombre', 'proveedor:id,nombre'])->get()
        );
    }

    public function conRefrigeracion(): Collection
    {
        return Cache::remember(
            'productos_refrigeracion',
            now()->addMinutes(20),
            fn () => $this->model->conRefrigeracion()->activos()->with(['categoria:id,nombre', 'proveedor:id,nombre'])->get()
        );
    }

    public function fraccionables(): Collection
    {
        return Cache::remember(
            'productos_fraccionables',
            now()->addMinutes(20),
            fn () => $this->model->fraccionables()->activos()->with(['categoria:id,nombre', 'proveedor:id,nombre'])->get()
        );
    }

    public function getEstadisticas(): array
    {
        return Cache::remember(
            'productos_estadisticas',
            now()->addMinutes(10),
            function () {
                $total = $this->model->count();
                $activos = $this->model->where('estado', EstadoEnum::ACTIVO->value)->count();
                $inactivos = $this->model->where('estado', EstadoEnum::INACTIVO->value)->count();
                $bajoStock = $this->model->bajoStock()->count();
                $sinStock = $this->model->sinStock()->count();
                $proximosVencer = $this->model->proximosAVencer(60)->count();
                $vencidos = $this->model->vencidos()->count();

                return [
                    'total' => $total,
                    'activos' => $activos,
                    'inactivos' => $inactivos,
                    'bajo_stock' => $bajoStock,
                    'sin_stock' => $sinStock,
                    'proximos_vencer' => $proximosVencer,
                    'vencidos' => $vencidos,
                    'porcentaje_activos' => $total > 0 ? round(($activos / $total) * 100, 2) : 0,
                    'porcentaje_bajo_stock' => $total > 0 ? round(($bajoStock / $total) * 100, 2) : 0,
                ];
            }
        );
    }

    public function bulkUpdate(array $updates): int
    {
        $affected = 0;

        foreach ($updates as $id => $data) {
            if ($this->model->where('id', $id)->update($data)) {
                $affected++;
                Cache::forget("producto_{$id}");
            }
        }

        if ($affected > 0) {
            $this->clearCache();
        }

        return $affected;
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        foreach ($filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            match ($key) {
                'categoria_id' => $query->porCategoria($value),
                'proveedor_id' => $query->porProveedor($value),
                'estado' => $query->conEstado([$value]),
                'con_stock' => $value ? $query->conStock() : null,
                'bajo_stock' => $value ? $query->bajoStock() : null,
                'proximos_vencer' => is_numeric($value) ? $query->proximosAVencer($value) : null,
                'refrigeracion' => $value ? $query->conRefrigeracion() : null,
                'fraccionables' => $value ? $query->fraccionables() : null,
                'precio_min' => isset($filters['precio_max']) ? $query->where('precio_venta', '>=', $value) : null,
                'precio_max' => isset($filters['precio_min']) ? $query->where('precio_venta', '<=', $value) : null,
                'laboratorio' => $query->porLaboratorio($value),
                default => null,
            };
        }
    }

    private function clearCache(): void
    {
        $patterns = [
            'productos_*',
            'productos_all',
            'productos_activos',
            'productos_inactivos',
            'productos_bajo_stock',
            'productos_sin_stock',
            'productos_proximos_vencer_*',
            'productos_estadisticas',
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }
}
