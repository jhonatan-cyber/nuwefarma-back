<?php

declare(strict_types=1);

namespace App\Http\Resources\Venta;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VentaResource extends JsonResource
{
    /**
     * Utilidad bruta de la venta cuando el costo fue calculado (withSum).
     */
    private function getUtilidadBruta(): ?float
    {
        if ($this->costo_venta === null) {
            return null;
        }

        return round((float) $this->total - (float) $this->costo_venta, 2);
    }

    /**
     * Margen de utilidad porcentual de la venta.
     */
    private function getMargenUtilidad(): ?float
    {
        $utilidad = $this->getUtilidadBruta();

        if ($utilidad === null || (float) $this->total <= 0) {
            return $utilidad === null ? null : 0.0;
        }

        return round(($utilidad / (float) $this->total) * 100, 2);
    }

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
            'type' => 'ventas',
            'attributes' => [
                'numero_venta' => $this->numero_venta,
                'tipo_pago' => $this->tipo_pago,
                'metodo_pago' => $this->metodo_pago,
                'subtotal' => $this->subtotal,
                'impuesto' => $this->impuestos,
                'descuento' => $this->descuento,
                'total' => $this->total,
                'pagado' => $this->pagado,
                'saldo_pendiente' => $this->saldo_pendiente,
                'estado' => $this->estado,
                'observaciones' => $this->observaciones,
                'motivo_cancelacion' => $this->motivo_cancelacion,
                'fecha_cancelacion' => $this->fecha_cancelacion,
                'fecha_vencimiento' => $this->fecha_vencimiento ? $this->fecha_vencimiento->format('Y-m-d') : null,
                'costo_venta' => $this->costo_venta !== null ? round((float) $this->costo_venta, 2) : null,
                'utilidad_bruta' => $this->getUtilidadBruta(),
                'margen_utilidad' => $this->getMargenUtilidad(),
            ],
            'relationships' => [
                'cliente' => $this->whenLoaded('cliente', fn() => [
                    'data' => [
                        'id' => $this->cliente->id,
                        'nombre' => $this->cliente->nombre,
                        'apellidos' => $this->cliente->apellidos,
                        'ci' => $this->cliente->ci,
                        'email' => $this->cliente->email,
                    ],
                ]),
                'usuario' => $this->whenLoaded('usuario', fn() => [
                    'data' => [
                        'id' => $this->usuario->id,
                        'nombre' => $this->usuario->nombre,
                        'email' => $this->usuario->email,
                    ],
                ]),
                'caja' => $this->whenLoaded('caja', fn() => [
                    'data' => [
                        'id' => $this->caja->id,
                        'nombre' => $this->caja->nombre,
                    ],
                ]),
                'venta_productos' => $this->whenLoaded('ventaProductos', fn() => 
                    $this->ventaProductos->map(fn($vp) => [
                        'id' => $vp->id,
                        'producto' => [
                            'id' => $vp->producto->id,
                            'nombre' => $vp->producto->nombre,
                            'codigo_barras' => $vp->producto->codigo_barras,
                        ],
                        'cantidad' => $vp->cantidad,
                        'precio_unitario' => $vp->precio_unitario,
                        'descuento' => $vp->descuento,
                        'subtotal' => $vp->subtotal,
                    ])
                ),
            ],
            'links' => [
                'self' => route('v1.ventas.show', $this->id),
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
