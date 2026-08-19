<?php

declare(strict_types=1);

namespace App\Http\Resources\Proveedor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProveedorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'nit' => $this->nit,
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'ciudad' => $this->ciudad,
            'pais' => $this->pais,
            'contacto' => $this->contacto,
            'telefono_contacto' => $this->telefono_contacto,
            'categoria' => $this->categoria,
            'observaciones' => $this->observaciones,
            'estado' => $this->estado,
            'productos_count' => $this->whenCounted('productos'),
            'compras_count' => $this->whenCounted('compras'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
