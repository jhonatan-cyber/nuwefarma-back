<?php

declare(strict_types=1);

namespace App\DTOs\Venta;

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
