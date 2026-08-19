<?php

declare(strict_types=1);

namespace App\Http\Resources\Medico;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'medicos',
            'attributes' => [
                'nombres' => $this->nombres,
                'apellidos' => $this->apellidos,
                'nombre_completo' => $this->nombre_completo,
                'ci' => $this->ci,
                'registro_profesional' => $this->registro_profesional,
                'especialidad' => $this->especialidad,
                'telefono' => $this->telefono,
                'email' => $this->email,
                'institucion' => $this->institucion,
                'estado' => $this->estado,
                'estado_label' => ucfirst($this->estado),
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
            'relationships' => [
                'recetas' => [
                    'meta' => [
                        'count' => $this->whenCounted('recetas'),
                    ],
                ],
            ],
            'links' => [
                'self' => route('v1.medicos.show', $this->id),
            ],
        ];
    }
}