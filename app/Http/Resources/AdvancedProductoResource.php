<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdvancedProductoResource extends JsonResource
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
                'nombre' => $this->nombre,
                'nombre_completo' => $this->nombre_completo,
                'descripcion' => $this->descripcion,
                'codigo_barras' => $this->codigo_barras,
                'sku' => $this->sku,
                'codigo_interno' => $this->codigo_interno,
                'laboratorio' => $this->laboratorio,
                'forma_farmaceutica' => $this->forma_farmaceutica,
                'concentracion' => $this->concentracion,
                'presentacion' => $this->presentacion,
                'via_administracion' => [
                    'value' => $this->via_administracion,
                    'label' => $this->via_administracion_label,
                ],
                'unidad_medida' => $this->unidad_medida,
                'fracciones_por_unidad' => $this->fracciones_por_unidad,
                'permite_fraccionar' => $this->permite_fraccionar,
                'lote' => $this->lote,
                'fecha_vencimiento' => $this->fecha_vencimiento?->toIso8601String(),
                'dias_para_vencer' => $this->diasParaVencer(),
                'registro_sanitario' => $this->registro_sanitario,
                'refrigeracion_requerida' => $this->refrigeracion_requerida,
                'dias_para_alertar_vencimiento' => $this->dias_para_alertar_vencimiento,
                'stock' => [
                    'actual' => $this->stock_actual,
                    'minimo' => $this->stock_minimo,
                    'maximo' => $this->stock_maximo,
                    'bajo_stock' => $this->bajo_stock,
                    'sin_stock' => $this->stock_actual <= 0,
                    'valor_total' => $this->stock_actual * $this->precio_venta,
                ],
                'precios' => [
                    'compra' => (float) $this->precio_compra,
                    'venta' => (float) $this->precio_venta,
                    'con_impuesto' => $this->precio_con_impuesto,
                    'margen_sugerido' => (float) $this->margen_sugerido,
                    'margen_real' => $this->margen_real,
                    'impuesto' => (float) $this->impuesto,
                ],
                'estado' => [
                    'value' => $this->estado,
                    'label' => $this->estado_label,
                    'color' => $this->estado_color,
                    'icon' => $this->getEstadoIcon(),
                ],
                'etiquetas' => $this->etiquetas ?? [],
                'fotos' => $this->fotos ?? [],
                'metadata' => [
                    'esta_vencido' => $this->estaVencido(),
                    'proximo_vencer' => $this->proximo_vencer,
                    'requiere_refrigeracion' => $this->refrigeracion_requerida,
                    'es_fraccionable' => $this->permite_fraccionar,
                    'tiene_stock' => $this->tieneStock(),
                ],
                'auditoria' => $this->when(
                    auth()->check() && auth()->user()->hasRole(['Administrador', 'Gerente']),
                    $this->auditoria
                ),
            ],
            'relationships' => [
                'categoria' => $this->when($this->categoria, [
                    'id' => $this->categoria->id,
                    'nombre' => $this->categoria->nombre,
                    'descripcion' => $this->categoria->descripcion ?? null,
                ]),
                'proveedor' => $this->when($this->proveedor, [
                    'id' => $this->proveedor->id,
                    'nombre' => $this->proveedor->nombre,
                    'contacto' => $this->proveedor->contacto ?? null,
                ]),
                'lotes' => $this->when(
                    $this->relationLoaded('lotes') && $this->lotes->isNotEmpty(),
                    $this->lotes->map(fn($lote) => [
                        'id' => $lote->id,
                        'numero' => $lote->numero,
                        'cantidad' => $lote->cantidad,
                        'fecha_vencimiento' => $lote->fecha_vencimiento?->toIso8601String(),
                        'estado' => $lote->estado,
                    ])
                ),
            ],
            'links' => [
                'self' => route('api.v2.productos.show', $this->id),
                'related' => [
                    'categoria' => $this->when($this->categoria_id, route('api.v2.categorias.show', $this->categoria_id)),
                    'proveedor' => $this->when($this->proveedor_id, route('api.v2.proveedores.show', $this->proveedor_id)),
                    'lotes' => route('api.v2.productos.lotes.index', $this->id),
                    'historial' => route('api.v2.productos.historial', $this->id),
                    'recomendaciones' => route('api.v2.productos.recommendations', $this->id),
                ],
                'actions' => [
                    'update' => route('api.v2.productos.update', $this->id),
                    'delete' => route('api.v2.productos.destroy', $this->id),
                    'toggle_estado' => route('api.v2.productos.toggle-status', $this->id),
                    'actualizar_stock' => route('api.v2.productos.update-stock', $this->id),
                    'exportar' => route('api.v2.productos.export', ['id' => $this->id]),
                ],
            ],
            'meta' => [
                'created_at' => $this->created_at->toIso8601String(),
                'updated_at' => $this->updated_at->toIso8601String(),
                'version' => '2.0',
                'cache_ttl' => 3600, // seconds
                'permissions' => [
                    'can_update' => auth()->check() && auth()->user()->can('update', $this),
                    'can_delete' => auth()->check() && auth()->user()->can('delete', $this),
                    'can_manage_stock' => auth()->check() && auth()->user()->can('manage-stock', $this),
                ],
            ],
        ];
    }

    /**
     * Get additional data that should be returned with the resource.
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'version' => 'v2',
                'api_version' => '2.0',
                'timestamp' => now()->toISOString(),
                'includes' => [
                    'categoria',
                    'proveedor', 
                    'lotes',
                    'auditoria',
                ],
            ],
        ];
    }

    /**
     * Customize the outgoing response for the resource.
     */
    public function withResponse(Request $request, \Illuminate\Http\JsonResponse $response): void
    {
        $response->header('X-API-Version', '2.0');
        $response->header('X-Resource-Type', 'producto');
        $response->header('X-Cache-Control', 'max-age=3600');
    }

    /**
     * Helper method to get estado icon
     */
    private function getEstadoIcon(): string
    {
        return match($this->estado) {
            'activo' => 'check-circle',
            'inactivo' => 'x-circle',
            'pendiente' => 'clock',
            'completado' => 'check-square',
            'cancelado' => 'x-square',
            'bloqueado' => 'alert-triangle',
            default => 'help-circle',
        };
    }
}
