<?php

namespace App\ValueObjects;

readonly class ProductPrice
{
    private float $purchasePrice;
    private float $sellingPrice;
    private float $tax;
    private float $margin;

    public function __construct(
        float $purchasePrice,
        float $sellingPrice,
        float $tax = 0.0
    ) {
        $this->validatePrices($purchasePrice, $sellingPrice, $tax);
        
        $this->purchasePrice = $purchasePrice;
        $this->sellingPrice = $sellingPrice;
        $this->tax = $tax;
        $this->margin = $this->calculateMargin();
    }

    public function getPurchasePrice(): float
    {
        return $this->purchasePrice;
    }

    public function getSellingPrice(): float
    {
        return $this->sellingPrice;
    }

    public function getTax(): float
    {
        return $this->tax;
    }

    public function getMargin(): float
    {
        return $this->margin;
    }

    public function getPriceWithTax(): float
    {
        return $this->sellingPrice * (1 + $this->tax / 100);
    }

    public function getProfit(): float
    {
        return $this->sellingPrice - $this->purchasePrice;
    }

    public function getProfitPercentage(): float
    {
        if ($this->purchasePrice === 0.0) {
            return 0.0;
        }

        return ($this->getProfit() / $this->purchasePrice) * 100;
    }

    public function isProfitable(): bool
    {
        return $this->sellingPrice > $this->purchasePrice;
    }

    public function toArray(): array
    {
        return [
            'purchase_price' => $this->purchasePrice,
            'selling_price' => $this->sellingPrice,
            'tax' => $this->tax,
            'margin' => $this->margin,
            'price_with_tax' => $this->getPriceWithTax(),
            'profit' => $this->getProfit(),
            'profit_percentage' => $this->getProfitPercentage(),
            'is_profitable' => $this->isProfitable(),
        ];
    }

    public function equals(self $other): bool
    {
        return $this->purchasePrice === $other->purchasePrice
            && $this->sellingPrice === $other->sellingPrice
            && $this->tax === $other->tax;
    }

    private function validatePrices(float $purchasePrice, float $sellingPrice, float $tax): void
    {
        if ($purchasePrice < 0) {
            throw new \InvalidArgumentException('Purchase price cannot be negative');
        }

        if ($sellingPrice < 0) {
            throw new \InvalidArgumentException('Selling price cannot be negative');
        }

        if ($tax < 0 || $tax > 100) {
            throw new \InvalidArgumentException('Tax must be between 0 and 100');
        }

        if ($sellingPrice <= $purchasePrice) {
            throw new \InvalidArgumentException('Selling price must be greater than purchase price');
        }
    }

    private function calculateMargin(): float
    {
        if ($this->purchasePrice === 0.0) {
            return 0.0;
        }

        return (($this->sellingPrice - $this->purchasePrice) / $this->purchasePrice) * 100;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['purchase_price'],
            $data['selling_price'],
            $data['tax'] ?? 0.0
        );
    }

    public static function createWithMargin(
        float $purchasePrice,
        float $marginPercentage,
        float $tax = 0.0
    ): self {
        $sellingPrice = $purchasePrice * (1 + $marginPercentage / 100);
        
        return new self($purchasePrice, $sellingPrice, $tax);
    }
}
