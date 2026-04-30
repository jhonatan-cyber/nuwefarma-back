<?php

declare(strict_types=1);

namespace App\DTOs\Venta;

readonly class CompleteVentaDTO
{
    public function __construct(
        public ?float $pagado = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            pagado: $data['pagado'] ?? null
        );
    }

    public static function fromRequest(array $data): self
    {
        return self::fromArray($data);
    }

    public function toArray(): array
    {
        return array_filter([
            'pagado' => $this->pagado,
        ], fn ($value) => $value !== null);
    }
}
