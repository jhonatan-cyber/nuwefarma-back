<?php

declare(strict_types=1);

namespace App\DTOs\Proveedor;

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
