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
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'sucursal' => $this->whenLoaded('sucursal', fn () => [
                'id' => $this->sucursal->id,
                'nombre' => $this->sucursal->nombre,
                'direccion' => $this->sucursal->direccion,
            ]),
            'gerente' => $this->whenLoaded('gerente', fn () => [
                'id' => $this->gerente->id,
                'nombre' => $this->gerente->nombre,
                'email' => $this->gerente->email,
            ]),
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
        ];
    }
}
