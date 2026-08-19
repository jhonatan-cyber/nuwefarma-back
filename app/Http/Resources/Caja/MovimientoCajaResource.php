<?php

declare(strict_types=1);

namespace App\Http\Resources\Caja;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovimientoCajaResource extends JsonResource
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
            'caja_id' => $this->caja_id,
            'tipo' => $this->tipo,
            'origen' => $this->origen,
            'documento_tipo' => $this->documento_tipo,
            'documento_id' => $this->documento_id,
            'documento_numero' => $this->documento_numero,
            'monto' => $this->monto,
            'saldo_despues' => $this->saldo_despues,
            'concepto' => $this->concepto,
            'usuario_id' => $this->usuario_id,
            'sucursal_id' => $this->sucursal_id,
            'created_at' => $this->created_at,
            'caja' => $this->whenLoaded('caja', fn () => $this->caja ? [
                'id' => $this->caja->id,
                'nombre' => $this->caja->nombre,
                'numero_caja' => $this->caja->numero_caja,
            ] : null),
        ];
    }
}