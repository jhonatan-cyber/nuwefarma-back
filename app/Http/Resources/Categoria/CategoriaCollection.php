<?php

declare(strict_types=1);

namespace App\Http\Resources\Categoria;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CategoriaCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(fn($categoria) => [
                'id' => $categoria->id,
                'type' => 'categorias',
                'attributes' => [
                    'nombre' => $categoria->nombre,
                    'descripcion' => $categoria->descripcion,
                    'estado' => $categoria->estado,
                    'created_at' => $categoria->created_at,
                ],
                'relationships' => [
                    'productos_count' => $categoria->productos_count ?? 0,
                ],
            ]),
            'meta' => [
                'total' => $this->total(),
                'count' => $this->collection->count(),
                'per_page' => $this->perPage() ?? null,
                'current_page' => $this->currentPage() ?? null,
                'last_page' => $this->lastPage() ?? null,
            ],
            'links' => [
                'first' => $this->url(1),
                'last' => $this->url($this->lastPage() ?? 1),
                'prev' => $this->previousPageUrl(),
                'next' => $this->nextPageUrl(),
            ],
        ];
    }
}
