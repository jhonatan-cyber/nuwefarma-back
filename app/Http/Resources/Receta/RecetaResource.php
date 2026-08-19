<?php

namespace App\Http\Resources\Receta;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecetaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'recetas',
            'attributes' => [
                'numero_receta' => $this->numero_receta,
                'fecha_emision' => $this->fecha_emision?->format('Y-m-d'),
                'fecha_vencimiento' => $this->fecha_vencimiento?->format('Y-m-d'),
                'observaciones' => $this->observaciones,
                'estado' => $this->estado,
                'created_at' => $this->created_at,
            ],
            'relationships' => [
                'medico' => $this->whenLoaded('medico', fn () => [
                    'data' => [
                        'id' => $this->medico->id,
                        'nombres' => $this->medico->nombres,
                        'apellidos' => $this->medico->apellidos,
                        'registro_profesional' => $this->medico->registro_profesional,
                        'especialidad' => $this->medico->especialidad,
                    ],
                ]),
                'paciente' => $this->whenLoaded('paciente', fn () => [
                    'data' => [
                        'id' => $this->paciente->id,
                        'nombres' => $this->paciente->nombres,
                        'apellidos' => $this->paciente->apellidos,
                        'ci' => $this->paciente->ci,
                    ],
                ]),
                'productos' => $this->whenLoaded('productos', fn () => $this->productos->map(fn ($item) => [
                    'id' => $item->id,
                    'producto' => $item->producto ? [
                        'id' => $item->producto->id,
                        'nombre' => $item->producto->nombre,
                        'codigo_barras' => $item->producto->codigo_barras,
                        'condicion_venta' => $item->producto->condicion_venta,
                        'es_controlado' => $item->producto->es_controlado,
                    ] : null,
                    'cantidad_prescrita' => $item->cantidad_prescrita,
                    'cantidad_dispensada' => $item->cantidad_dispensada,
                    'pendiente' => $item->getPendiente(),
                    'posologia' => $item->posologia,
                    'estado' => $item->estado,
                ])),
            ],
        ];
    }
}