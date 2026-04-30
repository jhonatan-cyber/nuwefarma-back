<?php

declare(strict_types=1);

namespace App\DTOs\Sucursal;

readonly class CreateSucursalDTO
{
    public function __construct(
        public string $nombre,
        public string $direccion,
        public string $telefono,
        public ?string $email,
        public string $ciudad,
        public string $departamento,
        public string $pais,
        public ?string $gerente_id = null,
        public ?int $capacidad_maxima = null,
        public ?string $horario_apertura = null,
        public ?string $horario_cierre = null,
        public ?string $dias_laborales = null,
        public ?string $descripcion = null,
        public string $estado = 'activo'
    ) {}
}
