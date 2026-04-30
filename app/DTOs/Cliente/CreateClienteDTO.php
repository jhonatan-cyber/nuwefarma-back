<?php

declare(strict_types=1);

namespace App\DTOs\Cliente;

readonly class CreateClienteDTO
{
    public function __construct(
        public string $nombre,
        public string $apellidos,
        public string $ci,
        public ?string $nit,
        public string $direccion,
        public string $telefono,
        public ?string $celular = null,
        public ?string $email = null,
        public ?string $fecha_nacimiento = null,
        public ?string $sexo = null,
        public ?string $estado_civil = null,
        public ?string $ocupacion = null,
        public ?string $referencia_nombre = null,
        public ?string $referencia_telefono = null,
        public ?float $limite_credito = 0.0,
        public ?int $dias_credito = 0,
        public ?string $observaciones = null,
        public string $estado = 'activo'
    ) {}
}
