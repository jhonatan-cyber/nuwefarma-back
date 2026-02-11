<?php

declare(strict_types=1);

namespace App\Http\Resources\Sucursal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SucursalResource extends JsonResource
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
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'ciudad' => $this->ciudad,
            'departamento' => $this->departamento,
            'pais' => $this->pais,
            'gerente' => $this->whenLoaded('gerente', fn() => [
                'id' => $this->gerente->id,
                'nombre' => $this->gerente->nombre,
                'email' => $this->gerente->email,
            ]),
            'capacidad_maxima' => $this->capacidad_maxima,
            'horario_apertura' => $this->horario_apertura,
            'horario_cierre' => $this->horario_cierre,
            'dias_laborales' => $this->dias_laborales,
            'descripcion' => $this->descripcion,
            'estado' => $this->estado,
            'usuarios_count' => $this->whenCounted('usuarios'),
            'cajas_count' => $this->whenCounted('cajas'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
