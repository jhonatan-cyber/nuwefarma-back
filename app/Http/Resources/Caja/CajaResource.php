<?php

declare(strict_types=1);

namespace App\Http\Resources\Caja;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CajaResource extends JsonResource
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
            'type' => 'cajas',
            'attributes' => [
                'nombre' => $this->nombre,
                'numero_caja' => $this->numero_caja,
                'saldo_inicial' => $this->saldo_inicial,
                'saldo_actual' => $this->saldo_actual,
                'saldo_final' => $this->saldo_final,
                'descripcion' => $this->descripcion,
                'estado' => $this->estado,
                'fecha_apertura' => $this->fecha_apertura,
                'fecha_cierre' => $this->fecha_cierre,
                'observaciones_cierre' => $this->observaciones_cierre,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
            'relationships' => [
                'sucursal' => $this->whenLoaded('sucursal', fn() => [
                    'data' => [
                        'id' => $this->sucursal->id,
                        'nombre' => $this->sucursal->nombre,
                        'direccion' => $this->sucursal->direccion,
                    ],
                ]),
                'usuario' => $this->whenLoaded('usuario', fn() => [
                    'data' => [
                        'id' => $this->usuario->id,
                        'nombre' => $this->usuario->nombre,
                        'email' => $this->usuario->email,
                    ],
                ]),
            ],
        ];
    }
}
