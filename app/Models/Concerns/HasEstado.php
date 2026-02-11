<?php

namespace App\Models\Concerns;

use App\Enums\EstadoEnum;
use Illuminate\Database\Eloquent\Builder;

trait HasEstado
{
    /**
     * Boot the trait
     */
    protected static function bootHasEstado(): void
    {
        static::creating(function ($model) {
            if (!isset($model->estado)) {
                $model->estado = EstadoEnum::ACTIVO->value;
            }
        });
    }

    /**
     * Scope to filter by active status
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', EstadoEnum::ACTIVO->value);
    }

    /**
     * Scope to filter by inactive status
     */
    public function scopeInactivos(Builder $query): Builder
    {
        return $query->where('estado', EstadoEnum::INACTIVO->value);
    }

    /**
     * Scope to filter by multiple estados
     */
    public function scopeConEstado(Builder $query, array $estados): Builder
    {
        return $query->whereIn('estado', $estados);
    }

    /**
     * Check if model is active
     */
    public function isActive(): bool
    {
        return $this->estado === EstadoEnum::ACTIVO->value;
    }

    /**
     * Check if model is inactive
     */
    public function isInactive(): bool
    {
        return $this->estado === EstadoEnum::INACTIVO->value;
    }

    /**
     * Get estado as enum
     */
    public function getEstadoEnum(): ?EstadoEnum
    {
        return EstadoEnum::tryFrom($this->estado);
    }

    /**
     * Set estado using enum
     */
    public function setEstadoEnum(EstadoEnum $estado): void
    {
        $this->estado = $estado->value;
    }

    /**
     * Activate the model
     */
    public function activate(): bool
    {
        return $this->update(['estado' => EstadoEnum::ACTIVO->value]);
    }

    /**
     * Deactivate the model
     */
    public function deactivate(): bool
    {
        return $this->update(['estado' => EstadoEnum::INACTIVO->value]);
    }

    /**
     * Toggle estado
     */
    public function toggleEstado(): bool
    {
        $nuevoEstado = $this->isActive() 
            ? EstadoEnum::INACTIVO 
            : EstadoEnum::ACTIVO;
            
        return $this->update(['estado' => $nuevoEstado->value]);
    }

    /**
     * Get estado label
     */
    public function getEstadoLabelAttribute(): string
    {
        return $this->getEstadoEnum()?->getLabel() ?? $this->estado;
    }

    /**
     * Get estado color
     */
    public function getEstadoColorAttribute(): string
    {
        return $this->getEstadoEnum()?->getColor() ?? 'gray';
    }
}
