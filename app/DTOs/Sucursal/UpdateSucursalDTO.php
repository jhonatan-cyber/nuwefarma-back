<?php

declare(strict_types=1);

namespace App\DTOs\Sucursal;

readonly class UpdateSucursalDTO
{
    public function __construct(
        public ?string $nombre = null,
        public ?string $direccion = null,
        public ?string $telefono = null,
        public ?string $email = null,
        public ?string $ciudad = null,
        public ?string $departamento = null,
        public ?string $pais = null,
        public ?string $gerente_id = null,
        public ?int $capacidad_maxima = null,
        public ?string $horario_apertura = null,
        public ?string $horario_cierre = null,
        public ?string $dias_laborales = null,
        public ?string $descripcion = null,
        public ?string $estado = null
    ) {}
}
