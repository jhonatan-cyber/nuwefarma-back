<?php

declare(strict_types=1);

namespace App\Http\Resources\NotaCredito;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotaCreditoResource extends JsonResource
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
            'numero' => $this->numero,
            'documento_tipo' => $this->documento_tipo,
            'documento_id' => $this->documento_id,
            'documento_numero' => $this->documento_numero,
            'monto' => $this->monto,
            'motivo' => $this->motivo,
            'estado' => $this->estado,
            'aplicado_a' => $this->aplicado_a,
            'usuario_id' => $this->usuario_id,
            'sucursal_id' => $this->sucursal_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'usuario' => $this->whenLoaded('usuario', fn () => $this->usuario ? [
                'id' => $this->usuario->id,
                'nombre' => $this->usuario->nombre,
                'apellidos' => $this->usuario->apellidos,
            ] : null),
        ];
    }
}