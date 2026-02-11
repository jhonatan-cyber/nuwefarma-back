<?php

declare(strict_types=1);

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'apellidos' => $this->apellidos,
            'ci' => $this->ci,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'foto' => $this->foto,
            'estado' => $this->estado,
            'rol' => [
                'id' => $this->rol->id,
                'nombre' => $this->rol->nombre,
                'descripcion' => $this->rol->descripcion,
            ],
            'sucursal' => $this->whenLoaded('sucursal', fn() => [
                'id' => $this->sucursal->id,
                'nombre' => $this->sucursal->nombre,
                'direccion' => $this->sucursal->direccion,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
