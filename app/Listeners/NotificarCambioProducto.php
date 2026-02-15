<?php

namespace App\Listeners;

use App\Events\ProductoActualizado;
use App\Models\Notificacion;
use App\Models\Usuario;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotificarCambioProducto implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ProductoActualizado $event): void
    {
        try {
            // Determinar usuarios a notificar
            $usuariosANotificar = $this->obtenerUsuariosANotificar($event);

            foreach ($usuariosANotificar as $usuario) {
                $this->crearNotificacionUsuario($usuario, $event);
            }

            Log::info('Notificaciones de cambio de producto enviadas', [
                'producto_id' => $event->producto->id,
                'usuarios_notificados' => $usuariosANotificar->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error enviando notificaciones de cambio de producto', [
                'producto_id' => $event->producto->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(ProductoActualizado $event, \Throwable $exception): void
    {
        Log::error('Fallo en notificación de cambio de producto', [
            'producto_id' => $event->producto->id,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Obtener usuarios que deben ser notificados
     */
    private function obtenerUsuariosANotificar(ProductoActualizado $event): \Illuminate\Database\Eloquent\Collection
    {
        // Notificar a administradores y gerentes
        return Usuario::whereHas('rol', function ($query) {
            $query->whereIn('nombre', ['Administrador', 'Gerente']);
        })->where('estado', 'activo')->get();
    }

    /**
     * Crear notificación para usuario específico
     */
    private function crearNotificacionUsuario(Usuario $usuario, ProductoActualizado $event): void
    {
        $cambios = $event->obtenerCambiosSignificativos();

        if (empty($cambios)) {
            return;
        }

        $titulo = 'Producto Actualizado';
        $mensaje = $this->generarMensajeNotificacion($event, $cambios);

        Notificacion::create([
            'usuario_id' => $usuario->id,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'tipo' => 'producto_actualizado',
            'datos' => [
                'producto_id' => $event->producto->id,
                'producto_nombre' => $event->producto->nombre,
                'cambios' => $cambios,
                'motivo' => $event->motivo,
                'usuario_actualizador_id' => $event->usuarioId,
            ],
            'leida' => false,
        ]);
    }

    /**
     * Generar mensaje de notificación basado en cambios
     */
    private function generarMensajeNotificacion(ProductoActualizado $event, array $cambios): string
    {
        $mensajesCambios = [];

        foreach ($cambios as $cambio) {
            $mensajeCambio = match ($cambio['campo']) {
                'precio_venta' => "Precio: {$cambio['valor_anterior']} → {$cambio['valor_nuevo']}",
                'stock_actual' => "Stock: {$cambio['valor_anterior']} → {$cambio['valor_nuevo']}",
                'estado' => "Estado: {$cambio['valor_anterior']} → {$cambio['valor_nuevo']}",
                'nombre' => "Nombre: {$cambio['valor_anterior']} → {$cambio['valor_nuevo']}",
                default => "{$cambio['label']}: {$cambio['valor_anterior']} → {$cambio['valor_nuevo']}",
            };

            $mensajesCambios[] = $mensajeCambio;
        }

        $mensajePrincipal = "El producto '{$event->producto->nombre}' ha sido actualizado.";
        $mensajeCambios = 'Cambios: '.implode(', ', $mensajesCambios);

        $motivo = $event->motivo ? "\nMotivo: {$event->motivo}" : '';

        return $mensajePrincipal."\n".$mensajeCambios.$motivo;
    }
}
