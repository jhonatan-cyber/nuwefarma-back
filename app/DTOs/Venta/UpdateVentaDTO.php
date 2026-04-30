<?php

declare(strict_types=1);

namespace App\DTOs\Venta;

readonly class UpdateVentaDTO
{
    public function __construct(
        public ?string $clienteId = null,
        public ?string $cajaId = null,
        public ?string $sucursalId = null,
        public ?string $metodoPago = null,
        public ?float $subtotal = null,
        public ?float $impuesto = null,
        public ?float $descuento = null,
        public ?float $total = null,
        public ?string $estado = null,
        public ?string $notas = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            clienteId: $data['cliente_id'] ?? null,
            cajaId: $data['caja_id'] ?? null,
            sucursalId: $data['sucursal_id'] ?? null,
            metodoPago: $data['metodo_pago'] ?? null,
            subtotal: $data['subtotal'] ?? null,
            impuesto: $data['impuestos'] ?? $data['impuesto'] ?? null,
            descuento: $data['descuento'] ?? null,
            total: $data['total'] ?? null,
            estado: $data['estado'] ?? null,
            notas: $data['notas'] ?? null
        );
    }

    public static function fromRequest(array $data): self
    {
        return self::fromArray($data);
    }

    public function toArray(): array
    {
        return array_filter([
            'cliente_id' => $this->clienteId,
            'caja_id' => $this->cajaId,
            'sucursal_id' => $this->sucursalId,
            'metodo_pago' => $this->metodoPago,
            'subtotal' => $this->subtotal,
            'descuento' => $this->descuento,
            'impuestos' => $this->impuesto,
            'total' => $this->total,
            'estado' => $this->estado,
            'notas' => $this->notas,
        ], fn ($value) => $value !== null);
    }
}
