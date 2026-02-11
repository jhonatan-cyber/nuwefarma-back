<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VentaResource extends JsonResource
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
            'type' => 'venta',
            'attributes' => [
                'numero_venta' => $this->numero_venta,
                'total' => (float) $this->total,
                'descuento' => (float) $this->descuento,
                'metodo_pago' => $this->metodo_pago,
                'estado' => $this->estado,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
            'relationships' => [
                'cliente' => $this->when($this->cliente, [
                    'id' => $this->cliente->id,
                    'nombre' => $this->cliente->nombre,
                    'ci' => $this->cliente->ci,
                ]),
                'usuario' => $this->when($this->usuario, [
                    'id' => $this->usuario->id,
                    'nombre' => $this->usuario->nombre,
                    'apellidos' => $this->usuario->apellidos,
                ]),
                'sucursal' => $this->when($this->sucursal, [
                    'id' => $this->sucursal->id,
                    'nombre' => $this->sucursal->nombre,
                ]),
                'productos' => $this->when($this->productos, function () {
                    return $this->productos->map(function ($ventaProducto) {
                        return [
                            'id' => $ventaProducto->id,
                            'producto_id' => $ventaProducto->producto_id,
                            'cantidad' => $ventaProducto->cantidad,
                            'precio_unitario' => (float) $ventaProducto->precio_unitario,
                            'descuento_unitario' => (float) $ventaProducto->descuento_unitario,
                            'subtotal' => (float) $ventaProducto->subtotal,
                        ];
                    });
                }),
            ],
            'links' => [
                'self' => "/api/ventas/{$this->id}",
                // 'comprobante' => route('api.v1.ventas.comprobante', $this->id), // Temporarily commented
            ],
        ];
    }
}
