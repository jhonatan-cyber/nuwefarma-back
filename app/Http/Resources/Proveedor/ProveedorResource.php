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
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'ruc' => $this->ruc,
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'contacto_nombre' => $this->contacto_nombre,
            'contacto_telefono' => $this->contacto_telefono,
            'contacto_email' => $this->contacto_email,
            'dias_credito' => $this->dias_credito,
            'limite_credito' => $this->limite_credito,
            'condiciones_pago' => $this->condiciones_pago,
            'observaciones' => $this->observaciones,
            'estado' => $this->estado,
            'productos_count' => $this->whenCounted('productos'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
