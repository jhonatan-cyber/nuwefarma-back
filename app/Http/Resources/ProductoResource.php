<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoResource extends JsonResource
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
            'type' => 'producto',
            'attributes' => [
                'id' => $this->id,
                'nombre' => $this->nombre,
                'codigo_barras' => $this->codigo_barras,
                'sku' => $this->sku,
                'codigo_interno' => $this->codigo_interno,
                'laboratorio' => $this->laboratorio,
                'concentracion' => $this->concentracion,
                'forma_farmaceutica' => $this->forma_farmaceutica,
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
                'precio_compra' => (float) $this->precio_compra,
                'precio_venta' => (float) $this->precio_venta,
                'margen_sugerido' => (float) $this->margen_sugerido,
                'impuesto' => (float) $this->impuesto,
                'etiquetas' => $this->etiquetas,
                'fotos' => $this->fotos,
                'descripcion' => $this->descripcion,
                'estado' => $this->estado,
                'nombre_completo' => $this->nombre_completo,
                'estado_label' => $this->estado_label,
                'estado_color' => $this->estado_color,
                'precio_con_impuesto' => $this->precio_con_impuesto,
                'margen_real' => $this->margen_real,
                'bajo_stock' => $this->bajo_stock,
                'proximo_vencer' => $this->proximo_vencer,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
            'relationships' => [
                'categoria' => $this->categoria ? [
                    'id' => $this->categoria->id,
                    'nombre' => $this->categoria->nombre,
                ] : null,
                'proveedor' => $this->proveedor ? [
                    'id' => $this->proveedor->id,
                    'nombre' => $this->proveedor->nombre,
                ] : null,
            ],
            'links' => [
                'self' => "/api/productos/{$this->id}",
            ],
        ];
    }
}
