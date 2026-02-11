<?php

namespace App\Events;

use App\Models\Producto;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductoActualizado
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Producto $producto,
        public array $datosAnteriores,
        public array $datosNuevos,
        public ?string $motivo = null,
        public ?int $usuarioId = null
    ) {
        $this->afterCommit = true;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('productos.' . $this->producto->id),
            new Channel('productos'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'producto.actualizado';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'producto_id' => $this->producto->id,
            'producto_nombre' => $this->producto->nombre,
            'cambios' => $this->obtenerCambiosSignificativos(),
            'motivo' => $this->motivo,
            'usuario_id' => $this->usuarioId,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Obtener cambios significativos para notificación
     */
    private function obtenerCambiosSignificativos(): array
    {
        $cambios = [];
        
        $camposImportantes = [
            'precio_venta' => 'Precio Venta',
            'stock_actual' => 'Stock Actual',
            'estado' => 'Estado',
            'nombre' => 'Nombre',
        ];

        foreach ($camposImportantes as $campo => $label) {
            $valorAnterior = $this->datosAnteriores[$campo] ?? null;
            $valorNuevo = $this->datosNuevos[$campo] ?? null;

            if ($valorAnterior !== $valorNuevo) {
                $cambios[] = [
                    'campo' => $campo,
                    'label' => $label,
                    'valor_anterior' => $valorAnterior,
                    'valor_nuevo' => $valorNuevo,
                ];
            }
        }

        return $cambios;
    }
}
