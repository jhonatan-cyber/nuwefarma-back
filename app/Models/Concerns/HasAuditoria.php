<?php

namespace App\Models\Concerns;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait HasAuditoria
{
    /**
     * Boot the trait
     */
    protected static function bootHasAuditoria(): void
    {
        static::creating(function (Model $model) {
            $model->crear_usuario_id = Auth::id();
        });

        static::updating(function (Model $model) {
            $model->actualizar_usuario_id = Auth::id();
        });
    }

    /**
     * Get the user who created the model
     */
    public function creador(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'crear_usuario_id');
    }

    /**
     * Get the user who last updated the model
     */
    public function actualizador(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'actualizar_usuario_id');
    }

    /**
     * Get creator name attribute
     */
    public function getCreadorNombreAttribute(): ?string
    {
        return $this->creador?->nombre_completo ?? 'Sistema';
    }

    /**
     * Get updater name attribute
     */
    public function getActualizadorNombreAttribute(): ?string
    {
        return $this->actualizador?->nombre_completo ?? 'Sistema';
    }

    /**
     * Scope to filter by creator
     */
    public function scopeCreadoPor($query, $userId)
    {
        return $query->where('crear_usuario_id', $userId);
    }

    /**
     * Scope to filter by updater
     */
    public function scopeActualizadoPor($query, $userId)
    {
        return $query->where('actualizar_usuario_id', $userId);
    }

    /**
     * Check if model was created by current user
     */
    public function esCreadoPorUsuarioActual(): bool
    {
        return $this->crear_usuario_id === Auth::id();
    }

    /**
     * Check if model was updated by current user
     */
    public function esActualizadoPorUsuarioActual(): bool
    {
        return $this->actualizar_usuario_id === Auth::id();
    }

    /**
     * Get audit trail information
     */
    public function getAuditoriaAttribute(): array
    {
        return [
            'creado' => [
                'usuario_id' => $this->crear_usuario_id,
                'usuario_nombre' => $this->creador_nombre,
                'fecha' => $this->created_at,
            ],
            'actualizado' => [
                'usuario_id' => $this->actualizar_usuario_id,
                'usuario_nombre' => $this->actualizador_nombre,
                'fecha' => $this->updated_at,
            ],
        ];
    }
}
