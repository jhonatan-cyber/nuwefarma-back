<?php

declare(strict_types=1);

namespace App\DTOs\Venta;

readonly class CreateVentaDTO
{
    public function __construct(
        public string $clienteId,
        public string $usuarioId,
        public string $cajaId,
        public string $sucursalId,
        public string $metodoPago,
        public float $subtotal,
        public float $impuesto,
        public float $descuento,
        public float $total,
        public string $estado,
        public ?string $notas = null,
        public array $productos = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            clienteId: $data['cliente_id'],
            usuarioId: $data['usuario_id'] ?? null,
            cajaId: $data['caja_id'],
            sucursalId: $data['sucursal_id'] ?? null,
            metodoPago: $data['metodo_pago'],
            subtotal: $data['subtotal'] ?? 0,
            impuesto: $data['impuestos'] ?? $data['impuesto'] ?? 0,
            descuento: $data['descuento'] ?? 0,
            total: $data['total'],
            estado: $data['estado'] ?? 'pendiente',
            notas: $data['notas'] ?? null,
            productos: $data['productos'] ?? []
        );
    }

    public static function fromRequest(array $data): self
    {
        return self::fromArray($data);
    }

    public function toArray(): array
    {
        return [
            'cliente_id' => $this->clienteId,
            'usuario_id' => $this->usuarioId,
            'caja_id' => $this->cajaId,
            'sucursal_id' => $this->sucursalId,
            'metodo_pago' => $this->metodoPago,
            'subtotal' => $this->subtotal,
            'descuento' => $this->descuento,
            'impuestos' => $this->impuesto,
            'total' => $this->total,
            'estado' => $this->estado,
            'notas' => $this->notas,
            'productos' => $this->productos,
        ];
    }
}
