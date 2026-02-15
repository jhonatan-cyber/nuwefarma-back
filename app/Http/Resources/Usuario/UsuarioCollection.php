<?php

declare(strict_types=1);

namespace App\Http\Resources\Usuario;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UsuarioCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(fn($usuario) => [
                'id' => $usuario->id,
                'type' => 'usuarios',
                'attributes' => [
                    'nombre' => $usuario->nombre,
                    'apellidos' => $usuario->apellidos,
                    'email' => $usuario->email,
                    'estado' => $usuario->estado,
                ],
                'relationships' => [
                    'rol' => [
                        'data' => [
                            'nombre' => $usuario->rol->nombre ?? null,
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
