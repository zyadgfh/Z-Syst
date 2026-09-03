<?php

namespace App\Domain\Product\ValueObjects;

class Quantity
{
    private int $value;
    private string $unit;

    public function __construct(int $value, string $unit = 'pcs')
    {
        if ($value < 0) {
            throw new \InvalidArgumentException('Quantity cannot be negative');
        }

        $this->value = $value;
        $this->unit = $unit;
    }

    public static function fromInt(int $value, string $unit = 'pcs'): self
    {
        return new self($value, $unit);
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function add(Quantity $other): Quantity
    {
        if ($this->unit !== $other->unit) {
            throw new \InvalidArgumentException('Cannot add quantities with different units');
        }

        return new self($this->value + $other->value, $this->unit);
    }

    public function subtract(Quantity $other): Quantity
    {
        if ($this->unit !== $other->unit) {
            throw new \InvalidArgumentException('Cannot subtract quantities with different units');
        }

        $newValue = $this->value - $other->value;
        if ($newValue < 0) {
            throw new \InvalidArgumentException('Resulting quantity cannot be negative');
        }

        return new self($newValue, $this->unit);
    }

    public function isZero(): bool
    {
        return $this->value === 0;
    }

    public function isPositive(): bool
    {
        return $this->value > 0;
    }

    public function isGreaterThan(Quantity $other): bool
    {
        if ($this->unit !== $other->unit) {
            throw new \InvalidArgumentException('Cannot compare quantities with different units');
        }

        return $this->value > $other->value;
    }

    public function isLessThan(Quantity $other): bool
    {
        if ($this->unit !== $other->unit) {
            throw new \InvalidArgumentException('Cannot compare quantities with different units');
        }

        return $this->value < $other->value;
    }

    public function equals(Quantity $other): bool
    {
        if ($this->unit !== $other->unit) {
            return false;
        }

        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return sprintf('%d %s', $this->value, $this->unit);
    }
}