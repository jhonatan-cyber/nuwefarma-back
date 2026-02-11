<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioResource extends JsonResource
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
            'type' => 'usuario',
            'attributes' => [
                'nombre' => $this->nombre,
                'apellidos' => $this->apellidos,
                'ci' => $this->ci,
                'direccion' => $this->direccion,
                'telefono' => $this->telefono,
                'email' => $this->email,
                'sueldo' => (float) $this->sueldo,
                'foto' => $this->foto,
                'estado' => $this->estado,
                'intentos_fallidos' => $this->intentos_fallidos,
                'bloqueado_hasta' => $this->bloqueado_hasta,
                'ultimo_intento_fallido' => $this->ultimo_intento_fallido,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
            'relationships' => [
                'rol' => $this->whenLoaded('rol', function () {
                    return $this->rol ? [
                        'id' => $this->rol->id,
                        'nombre' => $this->rol->nombre,
                    ] : null;
                }),
                'sucursal' => $this->whenLoaded('sucursal', function () {
                    return $this->sucursal ? [
                        'id' => $this->sucursal->id,
                        'nombre' => $this->sucursal->nombre,
                        'direccion' => $this->sucursal->direccion,
                    ] : null;
                }),
            ],
            'links' => [
                'self' => "/api/usuarios/{$this->id}",
            ],
        ];
    }
}
