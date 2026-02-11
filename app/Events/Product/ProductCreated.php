<?php

namespace App\Events\Product;

use App\Models\Producto;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Producto $producto,
        public readonly array $datosAdicionales = []
    ) {
        $this->afterCommit = true;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('productos.' . $this->producto->id),
            new Channel('productos'),
            new Channel('inventory'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'producto.creado';
    }

    public function broadcastWith(): array
    {
        return [
            'producto_id' => $this->producto->id,
            'producto_nombre' => $this->producto->nombre,
            'categoria_id' => $this->producto->categoria_id,
            'proveedor_id' => $this->producto->proveedor_id,
            'precio_venta' => $this->producto->precio_venta,
            'stock_actual' => $this->producto->stock_actual,
            'estado' => $this->producto->estado,
            'datos_adicionales' => $this->datosAdicionales,
            'timestamp' => now()->toISOString(),
            'event_type' => 'created',
        ];
    }
}
