<?php

declare(strict_types=1);

namespace App\DTOs\Categoria;

readonly class UpdateCategoriaDTO
{
    public function __construct(
        public ?string $nombre = null,
        public ?string $descripcion = null,
        public ?string $estado = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            nombre: $data['nombre'] ?? null,
            descripcion: $data['descripcion'] ?? null,
            estado: $data['estado'] ?? null
        );
    }

    public static function fromRequest(array $data): self
    {
        return self::fromArray($data);
    }

    public function toArray(): array
    {
        return array_filter([
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'estado' => $this->estado,
        ], fn ($value) => $value !== null);
    }
}
