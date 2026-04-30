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
                    'ci' => $usuario->ci,
                    'email' => $usuario->email,
                    'telefono' => $usuario->telefono,
                    'direccion' => $usuario->direccion,
                    'celular' => $usuario->celular,
                    'fecha_nacimiento' => $usuario->fecha_nacimiento,
                    'sexo' => $usuario->sexo,
                    'estado_civil' => $usuario->estado_civil,
                    'ocupacion' => $usuario->ocupacion,
                    'sueldo' => $usuario->sueldo,
                    'foto' => $usuario->foto,
                    'estado' => $usuario->estado,
                    'rol_id' => $usuario->rol_id,
                    'sucursal_id' => $usuario->sucursal_id,
                    'intentos_fallidos' => $usuario->intentos_fallidos,
                    'bloqueado_hasta' => $usuario->bloqueado_hasta,
                    'ultimo_intento_fallido' => $usuario->ultimo_intento_fallido,
                ],
                'relationships' => [
                    'rol' => [
                        'data' => [
                            'id' => $usuario->rol->id ?? null,
                            'nombre' => $usuario->rol->nombre ?? null,
                            'descripcion' => $usuario->rol->descripcion ?? null,
                        ],
                    ],
                    'sucursal' => [
                        'data' => [
                            'id' => $usuario->sucursal->id ?? null,
                            'nombre' => $usuario->sucursal->nombre ?? null,
                            'direccion' => $usuario->sucursal->direccion ?? null,
                        ],
                    ],
                ],
                'created_at' => $usuario->created_at,
                'updated_at' => $usuario->updated_at,
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
