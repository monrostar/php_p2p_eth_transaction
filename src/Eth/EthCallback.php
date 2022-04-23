<?php

namespace App\Eth;

/**
 * Class EthCallback made to get results from web3
 * @package App\Eth
 */
final class EthCallback
{
    /**
     * @var mixed|null
     */
    public $result = null;

    public function __invoke($error, $result)
    {
        $this->result = null;
        if ($error) {
            throw $error;
        }
        $this->result = $result;
    }

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance(): EthCallback
    {
        static $instance;

        if (null === $instance) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    private function __construct()
    {
    }

    /**
     * prevent the instance from being cloned (which would create a second instance of it)
     */
    private function __clone()
    {
    }

    /**
     * prevent from being unserialized (which would create a second instance of it)
     */
    private function __wakeup()
    {
    }
}

