<?php

declare(strict_types=1);

namespace App\Http\Resources\Producto;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoResource extends JsonResource
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
            'type' => 'productos',
            'attributes' => [
                'nombre' => $this->nombre,
                'codigo_barras' => $this->codigo_barras,
                'sku' => $this->sku,
                'codigo_interno' => $this->codigo_interno,
                'laboratorio' => $this->laboratorio,
                'forma_farmaceutica' => $this->forma_farmaceutica,
                'concentracion' => $this->concentracion,
                'presentacion' => $this->presentacion,
                'via_administracion' => $this->via_administracion,
                'unidad_medida' => $this->unidad_medida,
                'fracciones_por_unidad' => $this->fracciones_por_unidad,
                'permite_fraccionar' => $this->permite_fraccionar,
                'lote' => $this->lote,
                'fecha_vencimiento' => $this->fecha_vencimiento,
                'registro_sanitario' => $this->registro_sanitario,
                'refrigeracion_requerida' => $this->refrigeracion_requerida,
                'dias_para_alertar_vencimiento' => $this->dias_para_alertar_vencimiento,
                'stock_actual' => $this->stock_actual,
                'stock_minimo' => $this->stock_minimo,
                'stock_maximo' => $this->stock_maximo,
                'precio_compra' => $this->precio_compra,
                'precio_venta' => $this->precio_venta,
                'impuesto' => $this->impuesto,
                'precio_con_impuesto' => round($this->precio_venta * (1 + $this->impuesto / 100), 2),
                'bajo_stock' => $this->stock_actual <= $this->stock_minimo,
                'estado' => $this->estado,
                'estado_label' => ucfirst($this->estado),
            ],
            'relationships' => [
                'categoria' => $this->whenLoaded('categoria', fn() => [
                    'data' => [
                        'id' => $this->categoria->id,
                        'nombre' => $this->categoria->nombre,
                    ],
                ]),
                'proveedor' => $this->whenLoaded('proveedor', fn() => [
                    'data' => [
                        'id' => $this->proveedor->id,
                        'nombre' => $this->proveedor->nombre,
                        'ruc' => $this->proveedor->ruc,
                    ],
                ]),
            ],
            'links' => [
                'self' => route('productos.show', $this->id),
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
