<?php

declare(strict_types=1);

namespace App\Http\Resources\Caja;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArqueoCajaResource extends JsonResource
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
            'numero_arqueo' => $this->numero_arqueo,
            'caja_id' => $this->caja_id,
            'saldo_inicial' => $this->saldo_inicial,
            'total_ingresos' => $this->total_ingresos,
            'total_egresos' => $this->total_egresos,
            'saldo_sistema' => $this->saldo_sistema,
            'total_declarado' => $this->total_declarado,
            'total_contado' => $this->total_contado,
            'diferencia' => $this->diferencia,
            'detalles' => $this->detalles,
            'estado' => $this->estado,
            'observaciones' => $this->observaciones,
            'fecha_cierre' => $this->fecha_cierre,
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