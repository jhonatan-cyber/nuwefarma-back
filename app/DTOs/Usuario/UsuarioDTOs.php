<?php

declare(strict_types=1);

namespace App\DTOs\Usuario;

readonly class CreateUsuarioDTO
{
    public function __construct(
        public string $nombre,
        public string $apellidos,
        public string $ci,
        public string $email,
        public string $telefono,
        public ?string $direccion = null,
        public ?string $celular = null,
        public ?string $password = null,
        public ?string $fecha_nacimiento = null,
        public ?string $sexo = null,
        public ?string $estado_civil = null,
        public ?string $ocupacion = null,
        public ?string $referencia_nombre = null,
        public ?string $referencia_telefono = null,
        public ?float $limite_credito = 0.0,
        public ?int $dias_credito = 0,
        public ?string $rol_id = null,
        public ?string $sucursal_id = null,
        public ?float $sueldo = 0.0,
        public ?string $foto = null,
        public string $estado = 'activo'
    ) {}
}

readonly class UpdateUsuarioDTO
{
    public function __construct(
        public ?string $nombre = null,
        public ?string $apellidos = null,
        public ?string $ci = null,
        public ?string $email = null,
        public ?string $telefono = null,
        public ?string $direccion = null,
        public ?string $celular = null,
        public ?string $password = null,
        public ?string $fecha_nacimiento = null,
        public ?string $sexo = null,
        public ?string $estado_civil = null,
        public ?string $ocupacion = null,
        public ?string $referencia_nombre = null,
        public ?string $referencia_telefono = null,
        public ?float $limite_credito = null,
        public ?int $dias_credito = null,
        public ?string $rol_id = null,
        public ?string $sucursal_id = null,
        public ?float $sueldo = null,
        public ?string $foto = null,
        public ?string $estado = null
    ) {}
}

readonly class ListUsuariosDTO
{
    public function __construct(
        public ?string $search = null,
        public ?string $estado = null,
        public ?string $rol_id = null,
        public ?string $sucursal_id = null,
        public ?string $sort = 'nombre',
        public string $direction = 'asc',
        public int $per_page = 15
    ) {}
}

readonly class BulkUpdateUsuariosDTO
{
    public function __construct(
        /** @var array<string> */
        public array $ids,
        public string $estado
    ) {}
}
