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
