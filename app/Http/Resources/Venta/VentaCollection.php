<?php

declare(strict_types=1);

namespace App\Http\Resources\Venta;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class VentaCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(fn($venta) => [
                'id' => $venta->id,
                'type' => 'ventas',
                'attributes' => [
                    'numero_venta' => $venta->numero_venta,
                    'total' => $venta->total,
                    'metodo_pago' => $venta->metodo_pago,
                    'estado' => $venta->estado,
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
