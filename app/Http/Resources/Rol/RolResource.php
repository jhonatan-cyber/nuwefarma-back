<?php

declare(strict_types=1);

namespace App\Http\Resources\Rol;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RolResource extends JsonResource
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
            'descripcion' => $this->descripcion,
            'permiso_id' => $this->permiso_id,
            'estado' => $this->estado,
            'usuarios_count' => $this->whenCounted('usuarios'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
