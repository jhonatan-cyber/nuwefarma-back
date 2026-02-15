<?php

declare(strict_types=1);

namespace App\Http\Resources\Producto;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductoCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(fn($producto) => [
                'id' => $producto->id,
                'type' => 'productos',
                'attributes' => [
                    'nombre' => $producto->nombre,
                    'codigo_barras' => $producto->codigo_barras,
                    'precio_venta' => $producto->precio_venta,
                    'stock_actual' => $producto->stock_actual,
                    'stock_minimo' => $producto->stock_minimo,
                    'estado' => $producto->estado,
                ],
                'relationships' => [
                    'categoria' => [
                        'data' => [
                            'id' => $producto->categoria->id ?? null,
                            'nombre' => $producto->categoria->nombre ?? null,
                        ],
                    ],
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
