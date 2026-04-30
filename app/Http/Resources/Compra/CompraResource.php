<?php

declare(strict_types=1);

namespace App\Http\Resources\Compra;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompraResource extends JsonResource
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
            'proveedor' => $this->whenLoaded('proveedor', fn () => [
                'id' => $this->proveedor->id,
                'nombre' => $this->proveedor->nombre,
                'nit' => $this->proveedor->nit,
                'email' => $this->proveedor->email,
            ]),
            'usuario' => $this->whenLoaded('usuario', fn () => [
                'id' => $this->usuario->id,
                'nombre' => $this->usuario->nombre,
                'email' => $this->usuario->email,
            ]),
            'caja' => $this->whenLoaded('caja', fn () => [
                'id' => $this->caja->id,
                'nombre' => $this->caja->nombre,
            ]),
            'tipo_documento' => $this->tipo_documento,
            'numero_documento' => $this->numero_documento,
            'fecha_documento' => $this->fecha_documento,
            'subtotal' => $this->subtotal,
            'impuesto' => $this->impuesto,
            'descuento' => $this->descuento,
            'total' => $this->total,
            'pagado' => $this->pagado,
            'saldo_pendiente' => $this->saldo_pendiente,
            'estado' => $this->estado,
            'observaciones' => $this->observaciones,
            'compra_productos' => $this->whenLoaded('compraProductos', fn () => $this->compraProductos->map(fn ($cp) => [
                'id' => $cp->id,
                'producto' => [
                    'id' => $cp->producto->id,
                    'nombre' => $cp->producto->nombre,
                    'codigo_barras' => $cp->producto->codigo_barras,
                ],
                'cantidad' => $cp->cantidad,
                'precio_unitario' => $cp->precio_unitario,
                'descuento' => $cp->descuento,
                'subtotal' => $cp->subtotal,
                'lote' => $cp->lote,
                'fecha_vencimiento' => $cp->fecha_vencimiento,
            ])
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
