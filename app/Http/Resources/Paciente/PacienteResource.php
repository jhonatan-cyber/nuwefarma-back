<?php

declare(strict_types=1);

namespace App\Http\Resources\Paciente;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PacienteResource extends JsonResource
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
            'type' => 'pacientes',
            'attributes' => [
                'ci' => $this->ci,
                'nombres' => $this->nombres,
                'apellidos' => $this->apellidos,
                'nombre_completo' => $this->nombre_completo,
                'fecha_nacimiento' => $this->fecha_nacimiento?->format('Y-m-d'),
                'edad' => $this->edad,
                'sexo' => $this->sexo,
                'telefono' => $this->telefono,
                'email' => $this->email,
                'contacto_emergencia' => $this->contacto_emergencia,
                'observaciones' => $this->observaciones,
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
                'self' => route('v1.pacientes.show', $this->id),
            ],
        ];
    }
}