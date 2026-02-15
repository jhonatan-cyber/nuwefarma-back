<?php

namespace App\Enums;

enum ViaAdministracionEnum: string
{
    case ORAL = 'oral';
    case PARENTERAL = 'parenteral';
    case TOPICA = 'topica';
    case INHALATORIA = 'inhalatoria';
    case OFTALMICA = 'oftalmica';
    case OTICA = 'otica';
    case NASAL = 'nasal';
    case RECTAL = 'rectal';
    case VAGINAL = 'vaginal';

    public function getLabel(): string
    {
        return match ($this) {
            self::ORAL => 'Oral',
            self::PARENTERAL => 'Parenteral',
            self::TOPICA => 'Tópica',
            self::INHALATORIA => 'Inhalatoria',
            self::OFTALMICA => 'Oftálmica',
            self::OTICA => 'Ótica',
            self::NASAL => 'Nasal',
            self::RECTAL => 'Rectal',
            self::VAGINAL => 'Vaginal',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::ORAL => 'Administración por boca (tabletas, cápsulas, jarabes)',
            self::PARENTERAL => 'Inyección (IV, IM, SC)',
            self::TOPICA => 'Aplicación sobre la piel (cremas, ungüentos)',
            self::INHALATORIA => 'Inhalación (aerosoles, nebulizadores)',
            self::OFTALMICA => 'Aplicación en los ojos (gotas, pomadas)',
            self::OTICA => 'Aplicación en los oídos (gotas)',
            self::NASAL => 'Aplicación nasal (sprays, gotas)',
            self::RECTAL => 'Administración rectal (supositorios)',
            self::VAGINAL => 'Administración vaginal (óvulos, cremas)',
        };
    }

    public function getInstructions(): string
    {
        return match ($this) {
            self::ORAL => 'Tomar con agua, preferentemente con alimentos si causa irritación gástrica',
            self::PARENTERAL => 'Administración por personal médico capacitado, verificar dosis y via correcta',
            self::TOPICA => 'Aplicar sobre piel limpia y seca, masajear suavemente',
            self::INHALATORIA => 'Usar según indicaciones del dispositivo, mantener posición vertical',
            self::OFTALMICA => 'Aplicar en el oído afectado, mantener la cabeza inclinada por 5 minutos',
            self::OTICA => 'Aplicar en el oído afectado, mantener la cabeza inclinada por 5 minutos',
            self::NASAL => 'Aplicar en las fosas nasales, evitar sonarse la nariz inmediatamente',
            self::RECTAL => 'Introducir en el recto, preferentemente antes de dormir',
            self::VAGINAL => 'Introducir en la vagina, preferentemente antes de dormir',
        };
    }

    public function getFormasFarmaceuticas(): array
    {
        return match ($this) {
            self::ORAL => ['tabletas', 'cápsulas', 'jarabes', 'suspensiones', 'grageas', 'polvos'],
            self::PARENTERAL => ['inyectables', 'soluciones', 'emulsiones'],
            self::TOPICA => ['cremas', 'ungüentos', 'geles', 'lociones', 'parches'],
            self::INHALATORIA => ['aerosoles', 'inhaladores', 'nebulizadores'],
            self::OFTALMICA => ['gotas', 'pomadas', 'colirios'],
            self::OTICA => ['gotas', 'pomadas'],
            self::NASAL => ['sprays', 'gotas', 'inhaladores'],
            self::RECTAL => ['supositorios', 'enemas'],
            self::VAGINAL => ['óvulos', 'cremas', 'tabletas vaginales'],
        };
    }

    public function requiresMedicalSupervision(): bool
    {
        return match ($this) {
            self::PARENTERAL => true,
            default => false,
        };
    }

    public static function getAll(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->getLabel(),
            'description' => $case->getDescription(),
            'requires_medical_supervision' => $case->requiresMedicalSupervision(),
        ], self::cases());
    }

    public static function getCommon(): array
    {
        return [self::ORAL, self::TOPICA, self::PARENTERAL];
    }
}
