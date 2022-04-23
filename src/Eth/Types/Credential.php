<?php

namespace App\Eth\Types;

use Elliptic\EC;
use Exception;
use kornrunner\Keccak;
use Web3p\EthereumTx\Transaction as Web3Transaction;
use App\Eth\Types\Transaction as EthTransaction;

/**
 * Class Credential
 * @package App\Eth
 */
class Credential
{
    private $keyPair;

    /**
     * Credential constructor.
     * @param $keyPair
     */
    public function __construct($keyPair)
    {
        $this->keyPair = $keyPair;
    }

    /**
     * @return mixed
     */
    public function getPublicKey()
    {
        return $this->keyPair->getPublic()->encode('hex');
    }

    /**
     * @return mixed
     */
    public function getPrivateKey()
    {
        return $this->keyPair->getPrivate()->toString(16, 2);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getAddress(): string
    {
        $pubkey = $this->getPublicKey();
        return "0x" . substr(Keccak::hash(substr(hex2bin($pubkey), 1), 256), 24);
    }

    /**
     * @param EthTransaction $transaction
     * @return string
     * @throws Exception
     */
    public function signTransaction(EthTransaction $transaction): string
    {
        $txreq = new Web3Transaction($transaction->getHexRaw());
        $privateKey = $this->getPrivateKey();
        return '0x' . $txreq->sign($privateKey);
    }

    /**
     * @return Credential
     */
    public static function new(): Credential
    {
        $ec = new EC('secp256k1');
        $keyPair = $ec->genKeyPair();
        return new self($keyPair);
    }

    /**
     * @param $privateKey
     * @return Credential
     */
    public static function fromKey($privateKey): Credential
    {
        $ec = new EC('secp256k1');
        $keyPair = $ec->keyFromPrivate($privateKey);
        return new self($keyPair);
    }

    /**
     * @param $pass
     * @param $dir
     * @return string
     * @throws Exception
     */
    public static function newWallet($pass, $dir): string
    {
        $credential = self::new();
        $private = $credential->getPrivateKey();
        try {
            return KeyStore::save($private, $pass, $dir);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $pass
     * @param $wallet
     * @return Credential
     * @throws Exception
     */
    public static function fromWallet($pass, $wallet): Credential
    {
        $private = KeyStore::load($pass, $wallet);
        return self::fromKey($private);
    }
}
