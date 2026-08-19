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
            'numero_compra' => $this->numero_compra,
            'subtotal' => $this->subtotal,
            'descuento' => $this->descuento,
            'impuestos' => $this->impuestos,
            'total' => $this->total,
            'pagado' => $this->pagado,
            'saldo_pendiente' => $this->saldo_pendiente,
            'estado' => $this->estado,
            'metodo_pago' => $this->metodo_pago,
            'tipo_documento' => $this->tipo_documento,
            'numero_documento' => $this->numero_documento,
            'fecha_documento' => $this->fecha_documento,
            'fecha_vencimiento' => $this->fecha_vencimiento ? $this->fecha_vencimiento->format('Y-m-d') : null,
            'proveedor_id' => $this->proveedor_id,
            'usuario_id' => $this->usuario_id,
            'sucursal_id' => $this->sucursal_id,
            'notas' => $this->notas,
            'fecha_compra' => $this->fecha_compra,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'proveedor' => $this->whenLoaded('proveedor', fn () => $this->proveedor ? [
                'id' => $this->proveedor->id,
                'nombre' => $this->proveedor->nombre,
                'email' => $this->proveedor->email,
                'telefono' => $this->proveedor->telefono,
            ] : null),
            'usuario' => $this->whenLoaded('usuario', fn () => $this->usuario ? [
                'id' => $this->usuario->id,
                'nombre' => $this->usuario->nombre,
                'apellidos' => $this->usuario->apellidos,
                'email' => $this->usuario->email,
            ] : null),
            'sucursal' => $this->whenLoaded('sucursal', fn () => $this->sucursal ? [
                'id' => $this->sucursal->id,
                'nombre' => $this->sucursal->nombre,
                'direccion' => $this->sucursal->direccion,
            ] : null),
            'productos' => $this->whenLoaded('productos', fn () => $this->productos->map(fn ($cp) => [
                'id' => $cp->id,
                'compra_id' => $cp->compra_id,
                'producto_id' => $cp->producto_id,
                'lote_id' => $cp->lote_id,
                'numero_lote' => $cp->numero_lote,
                'fecha_vencimiento' => $cp->fecha_vencimiento ? $cp->fecha_vencimiento->format('Y-m-d') : null,
                'cantidad' => $cp->cantidad,
                'cantidad_recibida' => $cp->cantidad_recibida,
                'precio_unitario' => $cp->precio_unitario,
                'descuento_unitario' => $cp->descuento_unitario,
                'subtotal' => $cp->subtotal,
                'created_at' => $cp->created_at,
                'updated_at' => $cp->updated_at,
                'producto' => $cp->relationLoaded('producto') && $cp->producto ? [
                    'id' => $cp->producto->id,
                    'nombre' => $cp->producto->nombre,
                    'codigo_barras' => $cp->producto->codigo_barras,
                    'precio' => $cp->producto->precio_venta,
                ] : null,
            ])),
        ];
    }
}