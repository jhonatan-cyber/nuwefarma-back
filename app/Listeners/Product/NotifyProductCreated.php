<?php

namespace App\Listeners\Product;

use App\Events\Product\ProductCreated;
use App\Models\Notificacion;
use App\Models\Usuario;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyProductCreated implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public function handle(ProductCreated $event): void
    {
        try {
            $usuariosANotificar = $this->obtenerUsuariosANotificar();
            
            foreach ($usuariosANotificar as $usuario) {
                $this->crearNotificacionUsuario($usuario, $event);
            }

            Log::info('Notificaciones de producto creado enviadas', [
                'producto_id' => $event->producto->id,
                'usuarios_notificados' => $usuariosANotificar->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error enviando notificaciones de producto creado', [
                'producto_id' => $event->producto->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(ProductCreated $event, \Throwable $exception): void
    {
        Log::error('Fallo en notificación de producto creado', [
            'producto_id' => $event->producto->id,
            'error' => $exception->getMessage(),
        ]);
    }

    public function tags(): array
    {
        return ['notifications', 'product_created'];
    }

    private function obtenerUsuariosANotificar(): \Illuminate\Database\Eloquent\Collection
    {
        return Usuario::whereHas('rol', function ($query) {
            $query->whereIn('nombre', ['Administrador', 'Gerente', 'Editor']);
        })->where('estado', 'activo')->get();
    }

    private function crearNotificacionUsuario(Usuario $usuario, ProductCreated $event): void
    {
        Notificacion::create([
            'usuario_id' => $usuario->id,
            'titulo' => 'Nuevo Producto Creado',
            'mensaje' => "Se ha creado el producto '{$event->producto->nombre}' con precio de {$event->producto->precio_venta} y stock inicial de {$event->producto->stock_actual}.",
            'tipo' => 'producto_creado',
            'datos' => [
                'producto_id' => $event->producto->id,
                'producto_nombre' => $event->producto->nombre,
                'precio_venta' => $event->producto->precio_venta,
                'stock_actual' => $event->producto->stock_actual,
                'categoria_id' => $event->producto->categoria_id,
                'proveedor_id' => $event->producto->proveedor_id,
                'datos_adicionales' => $event->datosAdicionales,
                'timestamp' => now()->toDateTimeString(),
            ],
            'leida' => false,
        ]);
    }
}
