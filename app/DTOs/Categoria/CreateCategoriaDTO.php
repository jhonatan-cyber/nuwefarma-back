<?php

declare(strict_types=1);

namespace App\DTOs\Categoria;

readonly class CreateCategoriaDTO
{
    public function __construct(
        public string $nombre,
        public ?string $descripcion = null,
        public string $estado = 'activo'
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            nombre: $data['nombre'],
            descripcion: $data['descripcion'] ?? null,
            estado: $data['estado'] ?? 'activo'
        );
    }

    public static function fromRequest(array $data): self
    {
        return self::fromArray($data);
    }

    public function toArray(): array
    {
        return [
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'estado' => $this->estado,
        ];
    }
}
