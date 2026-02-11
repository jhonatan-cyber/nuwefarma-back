<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoriaResource extends JsonResource
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
            'type' => 'categoria',
            'attributes' => [
                'nombre' => $this->nombre,
                'descripcion' => $this->descripcion,
                'estado' => $this->estado,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
            'relationships' => [
                'productos_count' => $this->when(isset($this->productos_count), $this->productos_count),
            ],
            'links' => [
                'self' => route('api.v1.categorias.show', $this->id),
            ],
        ];
    }
}
