<?php


namespace App\Eth\Exceptions;


use Exception;

class InvalidBalanceException extends Exception
{
    public function __construct($minimumAvailableAmount)
    {
        parent::__construct("Amount must be greater than $minimumAvailableAmount");
    }
}
