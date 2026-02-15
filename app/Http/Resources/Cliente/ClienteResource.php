<?php

declare(strict_types=1);

namespace App\Http\Resources\Cliente;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteResource extends JsonResource
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
            'apellidos' => $this->apellidos,
            'ci' => $this->ci,
            'nit' => $this->nit,
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,
            'celular' => $this->celular,
            'email' => $this->email,
            'fecha_nacimiento' => $this->fecha_nacimiento,
            'sexo' => $this->sexo,
            'estado_civil' => $this->estado_civil,
            'ocupacion' => $this->ocupacion,
            'referencia_nombre' => $this->referencia_nombre,
            'referencia_telefono' => $this->referencia_telefono,
            'limite_credito' => $this->limite_credito,
            'dias_credito' => $this->dias_credito,
            'observaciones' => $this->observaciones,
            'estado' => $this->estado,
            'ventas_count' => $this->whenCounted('ventas'),
            'deuda_pendiente' => $this->whenAppended('deuda_pendiente'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Append calculated attributes.
     *
     * @return array<string>
     */
    public function with(Request $request): array
    {
        return ['deuda_pendiente'];
    }

    /**
     * Calculate pending debt.
     */
    protected function deudaPendiente(): float
    {
        return $this->resource->ventas()
            ->where('saldo_pendiente', '>', 0)
            ->sum('saldo_pendiente');
    }
}
