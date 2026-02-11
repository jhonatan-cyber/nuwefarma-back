<?php

namespace App\Enums;

enum MetodoPagoEnum: string
{
    case EFECTIVO = 'efectivo';
    case TARJETA_CREDITO = 'tarjeta_credito';
    case TARJETA_DEBITO = 'tarjeta_debito';
    case TRANSFERENCIA = 'transferencia';
    case CHEQUE = 'cheque';
    case QR = 'qr';
    case CREDITO_TIENDA = 'credito_tienda';

    public function getLabel(): string
    {
        return match($this) {
            self::EFECTIVO => 'Efectivo',
            self::TARJETA_CREDITO => 'Tarjeta de Crédito',
            self::TARJETA_DEBITO => 'Tarjeta de Débito',
            self::TRANSFERENCIA => 'Transferencia Bancaria',
            self::CHEQUE => 'Cheque',
            self::QR => 'QR',
            self::CREDITO_TIENDA => 'Crédito Tienda',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::EFECTIVO => 'cash',
            self::TARJETA_CREDITO => 'credit-card',
            self::TARJETA_DEBITO => 'credit-card',
            self::TRANSFERENCIA => 'bank',
            self::CHEQUE => 'document-text',
            self::QR => 'qr-code',
            self::CREDITO_TIENDA => 'store',
        };
    }

    public function requiresApproval(): bool
    {
        return match($this) {
            self::CHEQUE, self::TRANSFERENCIA, self::CREDITO_TIENDA => true,
            default => false,
        };
    }

    public function allowsInstallments(): bool
    {
        return match($this) {
            self::TARJETA_CREDITO, self::CREDITO_TIENDA => true,
            default => false,
        };
    }

    public function getCommissionRate(): float
    {
        return match($this) {
            self::TARJETA_CREDITO => 0.035, // 3.5%
            self::TARJETA_DEBITO => 0.020,  // 2.0%
            self::TRANSFERENCIA => 0.010,    // 1.0%
            self::QR => 0.015,              // 1.5%
            default => 0.0,
        };
    }

    public static function getAll(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->getLabel(),
            'icon' => $case->getIcon(),
            'requires_approval' => $case->requiresApproval(),
            'allows_installments' => $case->allowsInstallments(),
        ], self::cases());
    }

    public static function getImmediate(): array
    {
        return array_filter(self::cases(), fn($case) => !$case->requiresApproval());
    }

    public static function getWithApproval(): array
    {
        return array_filter(self::cases(), fn($case) => $case->requiresApproval());
    }
}
