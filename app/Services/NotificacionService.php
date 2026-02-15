<?php

namespace App\Services;

use App\Models\Lote;
use App\Models\Notificacion;
use Illuminate\Support\Facades\Log;

class NotificacionService
{
    public function crearNotificacion(array $data): Notificacion
    {
        return Notificacion::create($data);
    }

    public function getNotificacionesPendientes(?string $usuarioId = null): array
    {
        $query = Notificacion::pendientes()->orderBy('created_at', 'desc');

        if ($usuarioId) {
            $query->where(function ($q) use ($usuarioId) {
                $q->where('usuario_id', $usuarioId)
                    ->orWhereNull('usuario_id');
            });
        }

        return $query->get()->toArray();
    }

    public function getNotificacionesPorUsuario(string $usuarioId, int $limit = 50): array
    {
        return Notificacion::porUsuario($usuarioId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function marcarComoLeida(string $notificacionId): bool
    {
        $notificacion = Notificacion::find($notificacionId);
        if ($notificacion) {
            $notificacion->marcarComoLeida();

            return true;
        }

        return false;
    }

    public function marcarTodasComoLeidas(?string $usuarioId = null): int
    {
        $query = Notificacion::pendientes();

        if ($usuarioId) {
            $query->where(function ($q) use ($usuarioId) {
                $q->where('usuario_id', $usuarioId)
                    ->orWhereNull('usuario_id');
            });
        }

        return $query->update([
            'estado' => Notificacion::ESTADO_LEIDO,
            'leido_at' => now(),
        ]);
    }

    public function getCountPendientes(?string $usuarioId = null): int
    {
        $query = Notificacion::pendientes();

        if ($usuarioId) {
            $query->where(function ($q) use ($usuarioId) {
                $q->where('usuario_id', $usuarioId)
                    ->orWhereNull('usuario_id');
            });
        }

        return $query->count();
    }

    public function generarAlertasStockBajo(): int
    {
        $lotes = Lote::stockBajo()->with('producto')->get();
        $contador = 0;

        foreach ($lotes as $lote) {
            $existe = Notificacion::where('tipo', Notificacion::TIPO_STOCK_BAJO)
                ->where('registro_id', $lote->id)
                ->where('estado', Notificacion::ESTADO_PENDIENTE)
                ->exists();

            if (! $existe) {
                $this->crearNotificacion([
                    'tipo' => Notificacion::TIPO_STOCK_BAJO,
                    'titulo' => 'Stock Bajo',
                    'mensaje' => "El producto {$lote->producto->nombre} tiene stock bajo ({$lote->stock} unidades). Stock mínimo: {$lote->stock_minimo}",
                    'modulo' => 'Inventario',
                    'registro_id' => $lote->id,
                    'estado' => Notificacion::ESTADO_PENDIENTE,
                    'data' => [
                        'lote_id' => $lote->id,
                        'producto_id' => $lote->producto_id,
                        'producto_nombre' => $lote->producto->nombre,
                        'stock_actual' => $lote->stock,
                        'stock_minimo' => $lote->stock_minimo,
                    ],
                ]);
                $contador++;
            }
        }

        Log::info("Alertas de stock bajo generadas: {$contador}");

        return $contador;
    }

    public function generarAlertasProximoVencer(int $dias = 30): int
    {
        $lotes = Lote::proximosAVencer($dias)->with('producto')->get();
        $contador = 0;

        foreach ($lotes as $lote) {
            $existe = Notificacion::where('tipo', Notificacion::TIPO_PROXIMO_VENCER)
                ->where('registro_id', $lote->id)
                ->where('estado', Notificacion::ESTADO_PENDIENTE)
                ->exists();

            if (! $existe) {
                $this->crearNotificacion([
                    'tipo' => Notificacion::TIPO_PROXIMO_VENCER,
                    'titulo' => 'Producto Próximo a Vencer',
                    'mensaje' => "El producto {$lote->producto->nombre} (Lote: {$lote->numero_lote}) vence en {$lote->dias_para_vencer} días",
                    'modulo' => 'Inventario',
                    'registro_id' => $lote->id,
                    'estado' => Notificacion::ESTADO_PENDIENTE,
                    'data' => [
                        'lote_id' => $lote->id,
                        'producto_id' => $lote->producto_id,
                        'producto_nombre' => $lote->producto->nombre,
                        'numero_lote' => $lote->numero_lote,
                        'fecha_vencimiento' => $lote->fecha_vencimiento,
                        'dias_restantes' => $lote->dias_para_vencer,
                        'stock' => $lote->stock,
                    ],
                ]);
                $contador++;
            }
        }

        Log::info("Alertas de vencimiento próximas generadas: {$contador}");

        return $contador;
    }

    public function generarAlertasVencidos(): int
    {
        $lotes = Lote::where('estado', 'vencido')->with('producto')->get();
        $contador = 0;

        foreach ($lotes as $lote) {
            $existe = Notificacion::where('tipo', Notificacion::TIPO_VENCIDO)
                ->where('registro_id', $lote->id)
                ->where('estado', Notificacion::ESTADO_PENDIENTE)
                ->exists();

            if (! $existe) {
                $this->crearNotificacion([
                    'tipo' => Notificacion::TIPO_VENCIDO,
                    'titulo' => 'Producto Vencido',
                    'mensaje' => "El producto {$lote->producto->nombre} (Lote: {$lote->numero_lote}) está vencido desde {$lote->fecha_vencimiento}",
                    'modulo' => 'Inventario',
                    'registro_id' => $lote->id,
                    'estado' => Notificacion::ESTADO_PENDIENTE,
                    'data' => [
                        'lote_id' => $lote->id,
                        'producto_id' => $lote->producto_id,
                        'producto_nombre' => $lote->producto->nombre,
                        'numero_lote' => $lote->numero_lote,
                        'fecha_vencimiento' => $lote->fecha_vencimiento,
                        'stock' => $lote->stock,
                    ],
                ]);
                $contador++;
            }
        }

        Log::info("Alertas de productos vencidos generadas: {$contador}");

        return $contador;
    }

    public function generarTodasLasAlertas(): array
    {
        return [
            'stock_bajo' => $this->generarAlertasStockBajo(),
            'proximo_vencer' => $this->generarAlertasProximoVencer(),
            'vencidos' => $this->generarAlertasVencidos(),
        ];
    }

    public function limpiarNotificacionesAntiguas(int $dias = 30): int
    {
        return Notificacion::where('created_at', '<', now()->subDays($dias))
            ->where('estado', Notificacion::ESTADO_LEIDO)
            ->delete();
    }
}
