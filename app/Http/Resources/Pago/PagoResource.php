<?php

declare(strict_types=1);

namespace App\Http\Resources\Pago;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PagoResource extends JsonResource
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
            'numero_pago' => $this->numero_pago,
            'documento_tipo' => $this->documento_tipo,
            'documento_id' => $this->documento_id,
            'documento_numero' => $this->documento_numero,
            'fecha_pago' => $this->fecha_pago,
            'monto' => $this->monto,
            'metodo_pago' => $this->metodo_pago,
            'tipo_pago' => $this->tipo_pago,
            'referencia' => $this->referencia,
            'nota' => $this->nota,
            'caja_id' => $this->caja_id,
            'usuario_id' => $this->usuario_id,
            'sucursal_id' => $this->sucursal_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'caja' => $this->whenLoaded('caja', fn () => $this->caja ? [
                'id' => $this->caja->id,
                'nombre' => $this->caja->nombre,
                'numero_caja' => $this->caja->numero_caja,
            ] : null),
            'usuario' => $this->whenLoaded('usuario', fn () => $this->usuario ? [
                'id' => $this->usuario->id,
                'nombre' => $this->usuario->nombre,
                'apellidos' => $this->usuario->apellidos,
            ] : null),
        ];
    }
}