<?php

namespace App\Domain\Product\ValueObjects;

class Money
{
    private int $amount;
    private string $currency;

    public function __construct(int $amount, string $currency = 'EGP')
    {
        $this->amount = $amount;
        $this->currency = $currency;
    }

    public static function fromFloat(float $amount, string $currency = 'EGP'): self
    {
        return new self((int) round($amount * 100), $currency);
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function toFloat(): float
    {
        return $this->amount / 100;
    }

    public function add(Money $other): Money
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot add money with different currencies');
        }

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Money $other): Money
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot subtract money with different currencies');
        }

        return new self($this->amount - $other->amount, $this->currency);
    }

    public function multiply(float $multiplier): Money
    {
        return new self((int) round($this->amount * $multiplier), $this->currency);
    }

    public function isGreaterThan(Money $other): bool
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot compare money with different currencies');
        }

        return $this->amount > $other->amount;
    }

    public function isLessThan(Money $other): bool
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot compare money with different currencies');
        }

        return $this->amount < $other->amount;
    }

    public function equals(Money $other): bool
    {
        if ($this->currency !== $other->currency) {
            return false;
        }

        return $this->amount === $other->amount;
    }

    public function __toString(): string
    {
        return sprintf('%s %.2f', $this->currency, $this->toFloat());
    }
}