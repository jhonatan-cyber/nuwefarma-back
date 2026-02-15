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

readonly class ListVentasDTO
{
    public function __construct(
        public ?string $search = null,
        public ?string $estado = null,
        public ?string $tipoPago = null,
        public ?string $metodoPago = null,
        public ?string $clienteId = null,
        public ?string $usuarioId = null,
        public ?string $cajaId = null,
        public ?string $fechaInicio = null,
        public ?string $fechaFin = null,
        public ?float $totalMin = null,
        public ?float $totalMax = null,
        public ?bool $conSaldo = null,
        public ?string $sort = 'created_at',
        public string $direction = 'desc',
        public int $perPage = 15
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            search: $data['search'] ?? $data['q'] ?? null,
            estado: $data['estado'] ?? null,
            tipoPago: $data['tipo_pago'] ?? null,
            metodoPago: $data['metodo_pago'] ?? null,
            clienteId: $data['cliente_id'] ?? null,
            usuarioId: $data['usuario_id'] ?? null,
            cajaId: $data['caja_id'] ?? null,
            fechaInicio: $data['fecha_inicio'] ?? null,
            fechaFin: $data['fecha_fin'] ?? null,
            totalMin: $data['total_min'] ?? null,
            totalMax: $data['total_max'] ?? null,
            conSaldo: isset($data['con_saldo']) ? filter_var($data['con_saldo'], FILTER_VALIDATE_BOOLEAN) : null,
            sort: $data['sort'] ?? 'created_at',
            direction: $data['direction'] ?? $data['order'] ?? 'desc',
            perPage: (int) ($data['per_page'] ?? $data['perPage'] ?? 15)
        );
    }

    public static function fromRequest(array $data): self
    {
        return self::fromArray($data);
    }

    public function toArray(): array
    {
        return array_filter([
            'search' => $this->search,
            'estado' => $this->estado,
            'tipo_pago' => $this->tipoPago,
            'metodo_pago' => $this->metodoPago,
            'cliente_id' => $this->clienteId,
            'usuario_id' => $this->usuarioId,
            'caja_id' => $this->cajaId,
            'fecha_inicio' => $this->fechaInicio,
            'fecha_fin' => $this->fechaFin,
            'total_min' => $this->totalMin,
            'total_max' => $this->totalMax,
            'con_saldo' => $this->conSaldo,
            'sort' => $this->sort,
            'direction' => $this->direction,
            'per_page' => $this->perPage,
        ], fn ($value) => $value !== null);
    }
}

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
