<?php

namespace App\Enums;

enum EstadoEnum: string
{
    case ACTIVO = 'activo';
    case INACTIVO = 'inactivo';
    case PENDIENTE = 'pendiente';
    case COMPLETADO = 'completado';
    case CANCELADO = 'cancelado';
    case BLOQUEADO = 'bloqueado';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVO => 'Activo',
            self::INACTIVO => 'Inactivo',
            self::PENDIENTE => 'Pendiente',
            self::COMPLETADO => 'Completado',
            self::CANCELADO => 'Cancelado',
            self::BLOQUEADO => 'Bloqueado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ACTIVO => 'green',
            self::INACTIVO => 'gray',
            self::PENDIENTE => 'yellow',
            self::COMPLETADO => 'blue',
            self::CANCELADO => 'red',
            self::BLOQUEADO => 'orange',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::ACTIVO => 'check-circle',
            self::INACTIVO => 'x-circle',
            self::PENDIENTE => 'clock',
            self::COMPLETADO => 'check-square',
            self::CANCELADO => 'x-square',
            self::BLOQUEADO => 'alert-triangle',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVO;
    }

    public function isInactive(): bool
    {
        return $this === self::INACTIVO;
    }

    public function isPending(): bool
    {
        return $this === self::PENDIENTE;
    }

    public function isCompleted(): bool
    {
        return $this === self::COMPLETADO;
    }

    public function canTransitionTo(self $estado): bool
    {
        return match ($this) {
            self::ACTIVO => in_array($estado, [self::INACTIVO, self::BLOQUEADO, self::COMPLETADO]),
            self::INACTIVO => in_array($estado, [self::ACTIVO]),
            self::PENDIENTE => in_array($estado, [self::COMPLETADO, self::CANCELADO]),
            self::COMPLETADO => in_array($estado, [self::CANCELADO, self::ACTIVO]),
            self::CANCELADO => in_array($estado, [self::PENDIENTE]),
            self::BLOQUEADO => in_array($estado, [self::ACTIVO, self::INACTIVO]),
        };
    }

    public function getTransitionStates(): array
    {
        return match ($this) {
            self::ACTIVO => [self::INACTIVO, self::BLOQUEADO, self::COMPLETADO],
            self::INACTIVO => [self::ACTIVO],
            self::PENDIENTE => [self::COMPLETADO, self::CANCELADO],
            self::COMPLETADO => [self::CANCELADO, self::ACTIVO],
            self::CANCELADO => [self::PENDIENTE],
            self::BLOQUEADO => [self::ACTIVO, self::INACTIVO],
        };
    }

    public static function getAll(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }

    public static function getActives(): array
    {
        return [self::ACTIVO->value, self::COMPLETADO->value];
    }

    public static function getInactives(): array
    {
        return [self::INACTIVO->value, self::CANCELADO->value, self::BLOQUEADO->value];
    }

    public static function getSelectOptions(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->getLabel(),
            'color' => $case->getColor(),
            'icon' => $case->getIcon(),
        ], self::cases());
    }

    // Laravel 12+ features: advanced methods without constructor properties
    public function getDescription(): string
    {
        return match ($this) {
            self::ACTIVO => 'El recurso está activo y disponible',
            self::INACTIVO => 'El recurso está inactivo y no disponible',
            self::PENDIENTE => 'El recurso está pendiente de aprobación',
            self::COMPLETADO => 'El recurso ha sido completado exitosamente',
            self::CANCELADO => 'El recurso ha sido cancelado',
            self::BLOQUEADO => 'El recurso está bloqueado temporalmente',
        };
    }

    public function requiresAction(): bool
    {
        return match ($this) {
            self::PENDIENTE, self::BLOQUEADO => true,
            default => false,
        };
    }

    // Laravel 12+ advanced enum features
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->getLabel(),
            'color' => $this->getColor(),
            'icon' => $this->getIcon(),
            'description' => $this->getDescription(),
            'requires_action' => $this->requiresAction(),
            'transitions' => $this->getTransitionStates(),
        ];
    }
}
