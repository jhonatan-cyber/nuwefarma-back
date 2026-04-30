<?php

declare(strict_types=1);

namespace App\DTOs\Compra;

readonly class UpdateCompraDTO
{
    public function __construct(
        public ?string $tipo_documento = null,
        public ?string $numero_documento = null,
        public ?string $fecha_documento = null,
        public ?float $subtotal = null,
        public ?float $impuesto = null,
        public ?float $descuento = null,
        public ?float $total = null,
        public ?float $pagado = null,
        public ?float $saldo_pendiente = null,
        public ?string $estado = null,
        public ?string $observaciones = null
    ) {}
}
