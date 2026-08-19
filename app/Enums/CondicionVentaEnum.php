<?php

declare(strict_types=1);

namespace App\Enums;

enum CondicionVentaEnum: string
{
    case VENTA_LIBRE = 'venta_libre';
    case CON_RECETA = 'con_receta';
    case RECETA_RETENIDA = 'receta_retenida';

    public function getLabel(): string
    {
        return match ($this) {
            self::VENTA_LIBRE => 'Venta libre (OTC)',
            self::CON_RECETA => 'Venta con receta',
            self::RECETA_RETENIDA => 'Receta retenida (controlado)',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::VENTA_LIBRE => 'Medicamentos de venta libre sin prescripción médica',
            self::CON_RECETA => 'Requiere receta médica simple para su dispensación',
            self::RECETA_RETENIDA => 'Estupefacientes y psicotrópicos: receta retenida, autorización y libro de movimientos',
        };
    }

    /**
     * Si la condición implica medicamento controlado (receta retenida).
     */
    public function esControlado(): bool
    {
        return $this === self::RECETA_RETENIDA;
    }

    /**
     * Si la dispensación exige receta médica.
     */
    public function requiereReceta(): bool
    {
        return $this !== self::VENTA_LIBRE;
    }

    public static function getAll(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->getLabel(),
            'description' => $case->getDescription(),
            'es_controlado' => $case->esControlado(),
            'requiere_receta' => $case->requiereReceta(),
        ], self::cases());
    }
}