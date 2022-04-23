<?php


namespace App\Eth\Types;

class UnitWei
{
    private $amount;

    public function __construct($amount)
    {
        $this->amount = (string)$amount;
    }

    public function amount(): string
    {
        return $this->amount;
    }

    public function toNumber(): int {
        return $this->amount();
    }

    public function toEther(): string
    {
        return bcdiv($this->amount, 1000000000000000000, 18);
    }

    public function __toString(): string
    {
        return $this->amount;
    }
}
