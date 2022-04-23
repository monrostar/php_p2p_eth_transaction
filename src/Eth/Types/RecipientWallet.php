<?php


namespace App\Eth\Types;


use Exception;

class RecipientWallet
{
    /**
     * @var Address
     */
    protected $address;

    /**
     * The amount of eth in percentage that will go to this wallet
     * @var float
     */
    protected $amountPercent;

    /**
     * Request constructor.
     * @param $address
     * @param $amountPercent
     * @throws Exception
     */
    public function __construct($address, $amountPercent = 10)
    {
        if ($amountPercent < 0 && 100 > $amountPercent) {
            throw new Exception('feePercent must be greater than 0 and less than 100');
        }
        $this->amountPercent = $amountPercent;
        $this->address = new Address($address);
    }

    public function __toString(): string
    {
        return $this->address->toString();
    }

    /**
     * @return Address
     */
    public function getAddress(): Address
    {
        return $this->address;
    }

    /**
     * @return float
     */
    public function getAmountPercent()
    {
        return $this->amountPercent;
    }

    /**
     * @param UnitEther $amount
     * @return UnitEther
     */
    public function getAmount(UnitEther $amount): UnitEther
    {
        return new UnitEther($amount->amount() / 100 * $this->getAmountPercent());
    }

}
