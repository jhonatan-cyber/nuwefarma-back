<?php

declare(strict_types=1);

namespace App\Http\Resources\Caja;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CajaCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(fn($caja) => [
                'id' => $caja->id,
                'type' => 'cajas',
                'attributes' => [
                    'nombre' => $caja->nombre,
                    'numero_caja' => $caja->numero_caja,
                    'saldo_inicial' => $caja->saldo_inicial,
                    'saldo_actual' => $caja->saldo_actual,
                    'saldo_final' => $caja->saldo_final,
                    'estado' => $caja->estado,
                    'fecha_apertura' => $caja->fecha_apertura,
                    'fecha_cierre' => $caja->fecha_cierre,
                    'descripcion' => $caja->descripcion,
                ],
                'relationships' => [
                    'sucursal' => $caja->sucursal ? [
                        'id' => $caja->sucursal->id,
                        'nombre' => $caja->sucursal->nombre,
                    ] : null,
                    'usuario' => $caja->usuario ? [
                        'id' => $caja->usuario->id,
                        'nombre' => $caja->usuario->nombre,
                        'email' => $caja->usuario->email,
                    ] : null,
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
