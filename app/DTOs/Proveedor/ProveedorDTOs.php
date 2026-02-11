<?php

declare(strict_types=1);

namespace App\DTOs\Proveedor;

readonly class CreateProveedorDTO
{
    public function __construct(
        public string $nombre,
        public string $ruc,
        public string $direccion,
        public string $telefono,
        public ?string $email = null,
        public ?string $contacto_nombre = null,
        public ?string $contacto_telefono = null,
        public ?string $contacto_email = null,
        public ?int $dias_credito = 0,
        public ?float $limite_credito = 0.0,
        public ?string $condiciones_pago = null,
        public ?string $observaciones = null,
        public string $estado = 'activo'
    ) {}
}

readonly class UpdateProveedorDTO
{
    public function __construct(
        public ?string $nombre = null,
        public ?string $ruc = null,
        public ?string $direccion = null,
        public ?string $telefono = null,
        public ?string $email = null,
        public ?string $contacto_nombre = null,
        public ?string $contacto_telefono = null,
        public ?string $contacto_email = null,
        public ?int $dias_credito = null,
        public ?float $limite_credito = null,
        public ?string $condiciones_pago = null,
        public ?string $observaciones = null,
        public ?string $estado = null
    ) {}
}

readonly class ListProveedoresDTO
{
    public function __construct(
        public ?string $search = null,
        public ?string $estado = null,
        public ?string $ruc = null,
        public ?string $sort = 'nombre',
        public string $direction = 'asc',
        public int $per_page = 15
    ) {}
}

readonly class BulkUpdateProveedoresDTO
{
    public function __construct(
        /** @var array<string> */
        public array $ids,
        public string $estado
    ) {}
}
