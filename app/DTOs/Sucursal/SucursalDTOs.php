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

readonly class ListSucursalesDTO
{
    public function __construct(
        public ?string $search = null,
        public ?string $estado = null,
        public ?string $ciudad = null,
        public ?string $departamento = null,
        public ?string $pais = null,
        public ?string $gerente_id = null,
        public ?int $capacidad_min = null,
        public ?int $capacidad_max = null,
        public ?string $sort = 'nombre',
        public string $direction = 'asc',
        public int $per_page = 15
    ) {}
}

readonly class BulkUpdateSucursalesDTO
{
    public function __construct(
        /** @var array<string> */
        public array $ids,
        public string $estado
    ) {}
}
