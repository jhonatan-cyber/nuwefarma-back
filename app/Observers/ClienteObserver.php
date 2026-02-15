<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Cliente;

class ClienteObserver
{
    public function created(Cliente $cliente): void
    {
        ActivityLog::create([
            'usuario_id' => auth()->id(),
            'accion' => 'crear_cliente',
            'descripcion' => "Cliente {$cliente->nombre} {$cliente->apellidos} creado",
            'modulo' => 'Cliente',
            'registro_id' => $cliente->id,
        ]);
    }

    public function updated(Cliente $cliente): void
    {
        $cambios = $cliente->getChanges();
        unset($cambios['updated_at']);

        if (! empty($cambios)) {
            ActivityLog::create([
                'usuario_id' => auth()->id(),
                'accion' => 'actualizar_cliente',
                'descripcion' => "Cliente {$cliente->nombre} {$cliente->apellidos} actualizado",
                'modulo' => 'Cliente',
                'registro_id' => $cliente->id,
                'metadata' => json_encode($cambios),
            ]);
        }
    }

    public function deleted(Cliente $cliente): void
    {
        ActivityLog::create([
            'usuario_id' => auth()->id(),
            'accion' => 'eliminar_cliente',
            'descripcion' => "Cliente {$cliente->nombre} {$cliente->apellidos} eliminado",
            'modulo' => 'Cliente',
            'registro_id' => $cliente->id,
        ]);
    }
}
