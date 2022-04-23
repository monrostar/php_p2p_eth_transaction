<?php


namespace App\Eth\Types;


class UnitEther
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

    public function toNumber(): float {
        return (float)$this->amount();
    }

    public function toWei(): UnitWei
    {
        return new UnitWei(bcmul($this->amount, 1000000000000000000));
    }

    public function __toString(): string
    {
        return $this->amount;
    }
}
