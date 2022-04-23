<?php


namespace App\Eth;


use App\Eth\Types\Address;
use App\Eth\Types\Credential;
use App\Eth\Types\RecipientWallet;
use App\Eth\Types\Transaction;
use Exception;
use Throwable;

class TransactionCache
{
    /**
     * @param Credential $fromWallet
     * @return string
     * @throws Exception
     */
    public static function getFileName(Credential $fromWallet): string
    {
        $dir = __DIR__ . '/TxCache/';
        if (!is_dir($dir)) {
            // dir doesn't exist, make it
            mkdir($dir);
        }
        return $dir . $fromWallet->getAddress() . '.json';
    }

    /**
     * @param Transaction $transaction
     * @return bool
     * @throws Exception
     */
    public static function setCache(
        Transaction $transaction): bool
    {
        $latestTxHash = $transaction->getTransactionHash();
        if (!$latestTxHash) {
            throw new Exception('Not found TransactionHash');
        }

        if (!file_exists(self::getFileName($transaction->getCredential()))) {
            touch(self::getFileName($transaction->getCredential()));
        }

        $jsonContent = self::getJsonContent($transaction->getCredential());

        $jsonContent[strtolower($transaction->getToWallet()->toString())] = serialize($transaction);

        // encode array to json
        $json = json_encode($jsonContent);

        //write json to file
        if (file_put_contents(self::getFileName($transaction->getCredential()), $json))
            // success save
            return true;
        else
            // fail save
            return false;
    }

    /**
     * @param Credential $fromWallet
     * @param Address $recipientWallet
     * @return ?Transaction
     * @throws Exception
     */
    public static function getCache(Credential $fromWallet, Address $recipientWallet): ?Transaction
    {
        $jsonContent = self::getJsonContent($fromWallet);

        if (!$jsonContent) {
            return null;
        }
        $address = strtolower($recipientWallet->toString());
        if (isset($jsonContent[$address])) {
            return unserialize($jsonContent[$address]);
        }
        return null;
    }

    /**
     * @param Credential $fromWallet
     * @return ?array
     * @throws Exception
     */
    protected static function getJsonContent(Credential $fromWallet): ?array
    {
        if (!file_exists(self::getFileName($fromWallet))) {
            return null;
        }
        try {
            return json_decode(
                file_get_contents(self::getFileName($fromWallet)),
                true,
            );
        } catch (Exception | Throwable $e) {
            return null;
        }
    }
}
