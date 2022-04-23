<?php


namespace App\Eth;

use App\Eth\Exceptions\InvalidBalanceException;
use App\Eth\Types\Address;
use App\Eth\Types\BlockNumber;
use App\Eth\Types\Credential;
use App\Eth\Types\RecipientWallet;
use App\Eth\Types\TaskWallet;
use App\Eth\Types\Transaction;
use App\Eth\Types\TransactionInfo;
use App\Eth\Types\UnitEther;
use App\Eth\Types\UnitGwei;
use App\Eth\Types\UnitWei;
use App\Logger;
use DateTime;
use Exception;
use phpseclib\Math\BigInteger;
use Web3\Eth;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Web3;
set_time_limit(300); 
class EthService
{

    /**
     * @var \Monolog\Logger
     */
    protected $log;

    public const GAS_LIMIT_LOW = 'low';
    public const GAS_LIMIT_MEDIUM = 'medium';
    public const GAS_LIMIT_HIGH = 'high';

    public const MINIMUM_AVAILABLE_ETH_AMOUNT_PER_TRANSACTION = 0.0008;

    /** @var Web3 */
    protected $web3;
    /** @var Eth */
    protected $eth;
    protected $chainId = Transaction::CHAIN_ID_MAINNET;
    /**
     * @var bool
     */
    protected $isTestNet;

    public function __construct($network = null, bool $isTestNet = False)
    {
        $this->isTestNet = $isTestNet;
        if ($this->isTestNet) {
            $this->chainId = Transaction::CHAIN_ID_RINKEBY;
        }
        $this->web3 = new Web3(new HttpProvider(new HttpRequestManager($network, 10)));
        $this->eth = $this->web3->getEth();
        $this->log = Logger::init();
    }

    /**
     * @param false $isTest
     * @return self
     */
    public static function getInstance($isTest = false): self
    {
        if ($isTest) {
            $network = "https://rinkeby.infura.io/v3/12e1a4372dae4d3886806ca2462edd47";
        } else {
            $network = "https://mainnet.infura.io/v3/12e1a4372dae4d3886806ca2462edd47";
        }

        return new self($network, $isTest);
    }

    /**
     * @param string $walletAddress
     * @param BlockNumber|null $fromBlockNumber
     * @param BlockNumber|null $toBlockNumber
     * @return mixed|null
     */
    public function getLogs(string $walletAddress, BlockNumber $fromBlockNumber = null, BlockNumber $toBlockNumber = null)
    {
        if (!$fromBlockNumber) $fromBlockNumber = BlockNumber::earliest()->toString();
        if (!$toBlockNumber) $toBlockNumber = BlockNumber::latest()->toString();
        $cb = EthCallback::getInstance();
        $this->eth->getLogs(
            [
                'address' => $walletAddress,
                'fromBlock' => $fromBlockNumber->toString(),
                'toBlock' => $toBlockNumber->toString(),
            ],
            $cb
        );
        return $cb->result;
    }

    /**
     * @param string $walletAddress
     * @param ?BlockNumber $blockNumber
     * @return BigInteger
     */
    public function getTransactionCount(string $walletAddress, BlockNumber $blockNumber = null): BigInteger
    {
        if (!$blockNumber) $blockNumber = BlockNumber::latest();
        $cb = EthCallback::getInstance();
        $this->eth->getTransactionCount($walletAddress, $blockNumber->toString(), $cb);
        return $cb->result;
    }


    /**
     * @param RecipientWallet[] $toWallets
     * @param UnitEther $totalAmount
     * @return void
     * @throws Exception
     */
    protected function validSumOfWallets100Percent(array $toWallets, UnitEther $totalAmount): void
    {
        $maximumAmountPercent = 100;
        $amountPercentFromWallets = 0;
        $totalAmountFromWallets = 0.0;
        foreach ($toWallets as $toWallet) {
            $amountPercentFromWallets += $toWallet->getAmountPercent();
            $totalAmountFromWallets += (float)$toWallet->getAmount($totalAmount)->amount();
        }

        if ($amountPercentFromWallets !== $maximumAmountPercent) {
            throw new Exception('The total amount of ETH should be 100 percent of the total of all wallets.');
        }

        if ((string)$totalAmountFromWallets !== (string)$totalAmount->toNumber()) {
            throw new Exception("The total amount of ETH from all RecipientWallets:$totalAmountFromWallets != FromWallet:$totalAmount.");
        }
    }

    /**
     * @param TaskWallet[] $taskWallets
     * @param ?UnitEther $minAvailableBalance
     * @param ?UnitEther $feeDelta
     * @param ?UnitEther $maxBalanceAmountPerIteration
     * @param ?UnitGwei $maxGasPrice
     * @return array
     * @throws Exception
     */
    public function sendByTasks(
        array $taskWallets,
        UnitEther $minAvailableBalance = null,
        UnitEther $feeDelta = null,
        UnitEther $maxBalanceAmountPerIteration = null,
        UnitGwei $maxGasPrice = null): array
    {
        if (!$minAvailableBalance) $minAvailableBalance = new UnitEther(0.1);
        if (!$feeDelta) $feeDelta = new UnitEther(0.01);
        $res = [];

        foreach ($taskWallets as $taskWallet) {
            $credential = $taskWallet->getCredential();

            $gasPriceInGwei = $this->getCurrentGasPrices()[EthService::GAS_LIMIT_MEDIUM];
            if ($maxGasPrice != null && $gasPriceInGwei->toNumber() > $maxGasPrice->toNumber()) {
                $this->log->warning("Do nothing. Too expensive gas {$gasPriceInGwei->amount()} > {$maxGasPrice->amount()}");
                continue;
            }

            $currentBalance = $this->getWalletBalance($credential->getAddress());
            // If you need to send a fixed value of the currency from the wallet, you can specify it here
            $walletBalance = $this->calcWalletBalance(
                $taskWallet,
                $gasPriceInGwei,
                $feeDelta,
                $currentBalance,
                $maxBalanceAmountPerIteration
            );
            if ($minAvailableBalance->toNumber() > (float)$currentBalance->toEther()) {
                $this->log->warning("Do nothing. Not enough eth for a transaction. $minAvailableBalance < {$currentBalance->toEther()} ETH on {$credential->getAddress()}");
                continue;
            }

            if ($walletBalance->toNumber() >= (float)$currentBalance->toEther()) {
                $this->log->warning("Do nothing. Not enough eth for a transaction. $walletBalance >= {$currentBalance->toEther()} ETH on {$credential->getAddress()}");
                continue;
            }
            try {
                $res[$credential->getAddress()] = $this->sendMultiple(
                    $credential,
                    $taskWallet->getRecipientWallets(),
                    $walletBalance,
                    $gasPriceInGwei,
                );
            } catch (Exception $e) {
                $this->log->error('Method sendMultiple()', ['taskWallet' => json_encode($taskWallet), 'error' => $e]);
            }
        }
        return $res;
    }

    /**
     * @param TaskWallet $taskWallet
     * @param UnitGwei $gasPrice
     * @param UnitEther $feeDelta
     * @param UnitWei $currentBalance
     * @param ?UnitEther $maxBalanceAmount
     * @return UnitEther
     * @throws Exception
     */
    protected function calcWalletBalance(TaskWallet $taskWallet,
                                         UnitGwei $gasPrice,
                                         UnitEther $feeDelta,
                                         UnitWei $currentBalance,
                                         UnitEther $maxBalanceAmount = null): UnitEther
    {
        $gasPriceInEther = $gasPrice->toEther();
        $countOfWallets = count($taskWallet->getRecipientWallets());
        $totalNeededGweiInEther = $gasPriceInEther * $countOfWallets;
        $walletBalance = $currentBalance->toEther();
        $delta = $totalNeededGweiInEther + (float)$feeDelta->amount();
        if ($maxBalanceAmount !== null && (float)$maxBalanceAmount->amount() <= $walletBalance) {
            return  new UnitEther((float)$maxBalanceAmount->amount() - $delta);
        }
        return new UnitEther($walletBalance - ($totalNeededGweiInEther + (float)$feeDelta->amount()));
    }

    /**
     * @param Credential $credential
     * @param RecipientWallet[] $toWallets
     * @param UnitEther $amount
     * @param UnitGwei $gasPrice
     * @param string $data
     * @return array
     * @throws InvalidBalanceException
     * @throws Exception
     */
    public function sendMultiple(
        Credential $credential,
        array $toWallets,
        UnitEther $amount,
        UnitGwei $gasPrice,
        $data = ''
    ): array
    {
        $txListResult = [];
        $pdTxList = $this->getPendingOrDroppedTransaction($credential, $toWallets);
        if (count($pdTxList) > 0) {
            // If there are pending transactions, re-send and skip iteration.
            $txListResult = $this->resendTransactions($txListResult, $pdTxList);
            return $txListResult;
        }
        $this->validSumOfWallets100Percent($toWallets, $amount);
        $nonce = (int)$this->getTransactionCount($credential->getAddress(), BlockNumber::pending())->toString();
        foreach ($toWallets as $i => $toWallet) {
            $newNonce = $nonce + $i;
            $transaction = $this->send(
                $credential,
                $toWallet->getAddress(),
                $toWallet->getAmount($amount),
                $gasPrice,
                $newNonce,
                $data
            );
            $this->setTxListResult($txListResult, $transaction);
        }
        return $txListResult;
    }

    /**
     * @param Transaction[] $txListResult
     * @param Transaction[] $transactions
     * @return Transaction[]
     * @throws InvalidBalanceException
     * @throws Exception
     */
    public function resendTransactions(array &$txListResult, array $transactions): array
    {
        $hourIntervalForPendingTx = 6;
        foreach ($transactions as $transaction) {
            $interval = $transaction->getCreatedAt()->diff(new DateTime());
            if ($interval->h < $hourIntervalForPendingTx) {
                continue;
            }
            $newGasPrice = $this->getCurrentGasPrices()[self::GAS_LIMIT_MEDIUM];
            $transaction = $this->send(
                $transaction->getCredential(),
                $transaction->getToWallet(),
                $transaction->getValue(),
                $newGasPrice,
                $transaction->getNonce(),
                $transaction->getData()
            );
            $this->setTxListResult($txListResult, $transaction);
        }
        return $txListResult;
    }

    /**
     * @param Transaction[] $txListResult
     * @param Transaction $transaction
     * @return Transaction[]
     * @throws Exception
     */
    public function setTxListResult(array &$txListResult, Transaction $transaction): array
    {
        $txListResult[$transaction->getToWallet()->toString()] = $transaction->getRaw();
        TransactionCache::setCache($transaction);
        return $txListResult;
    }

    /**
     * @param UnitEther $amount
     * @throws InvalidBalanceException
     */
    public function isValidAmount(UnitEther $amount): void
    {
        $minimumAvailableAmount = self::MINIMUM_AVAILABLE_ETH_AMOUNT_PER_TRANSACTION;
        if ($amount->amount() < $minimumAvailableAmount) {
            throw new InvalidBalanceException($minimumAvailableAmount);
        }
    }

    /**
     * @param Credential $credential
     * @param Address $toWallet
     * @param UnitEther $amount
     * @param UnitGwei $gasPrice
     * @param string $message
     * @param int|null $nonce
     * @return Transaction
     * @throws Exception|InvalidBalanceException
     */
    protected function send(
        Credential $credential,
        Address $toWallet,
        UnitEther $amount,
        UnitGwei $gasPrice,
        ?int $nonce = null,
        $message = ''): Transaction
    {
        $this->isValidAmount($amount);
        $transaction = $this->sendRawTransaction($credential, $toWallet, $amount, $gasPrice, $nonce, $message);
        $this->waitForReceipt($transaction->getTransactionHash());
        return $transaction;
    }

    /**
     * @param Credential $credential
     * @param Address $toWallet
     * @param UnitEther $amount
     * @param UnitGwei $gasPrice
     * @param int|null $nonce
     * @param string $message
     * @return Transaction
     * @throws Exception
     */
    public function sendRawTransaction(
        Credential $credential,
        Address $toWallet,
        UnitEther $amount,
        UnitGwei $gasPrice,
        ?int $nonce = null,
        $message = ''): Transaction
    {
        $cb = EthCallback::getInstance();
        if (!$nonce) {
            $nonce = (int)$this->getTransactionCount($credential->getAddress(), BlockNumber::pending())->toString();
        }
        $transaction = new Transaction($credential, $toWallet, $amount, $gasPrice, $this->chainId, $nonce, $message);
        $signed = $credential->signTransaction($transaction);
        try {
            $this->eth->sendRawTransaction($signed, $cb);
            $transaction->setTransactionHash($cb->result);
            $transaction->setCreatedAtNow();
        } catch (Exception $e) {
            $this->log->error(
                "SEND ERROR: raw transaction $signed",
                [
                    'raw' => $transaction->getRaw(),
                    'error' => $e
                ],
            );
            throw $e;
        }
        $this->log->info(
            "SEND SUCCESS: raw transaction $signed",
            [
                'raw' => $transaction->getRaw()
            ],
        );
        return $transaction;
    }

    /**
     * @param string $privateKey
     * @param Address $toWallet
     * @param UnitEther $amount
     * @param UnitGwei $gasPrice
     * @param string $message
     * @return Transaction
     * @throws InvalidBalanceException
     */
    public function sendFromKey(
        string $privateKey,
        Address $toWallet,
        UnitEther $amount,
        UnitGwei $gasPrice,
        $message = ''): Transaction
    {
        $credential = Credential::fromKey($privateKey);
        return $this->send($credential, $toWallet, $amount, $gasPrice, null, $message);
    }

    /**
     * @param string $fromWallet
     * @param string $fromPassword
     * @param Address $toWallet
     * @param UnitEther $amount
     * @param UnitGwei $gasPrice
     * @param string $message
     * @return Transaction
     * @throws InvalidBalanceException
     */
    public function sendFromPassword(
        string $fromWallet,
        string $fromPassword,
        Address $toWallet,
        UnitEther $amount,
        UnitGwei $gasPrice,
        $message = ''): Transaction
    {
        $credential = Credential::fromWallet($fromPassword, $fromWallet);
        return $this->send($credential, $toWallet, $amount, $gasPrice, null, $message);
    }

    /**
     * @param string $txHash
     * @param int $timeout
     * @param int $interval
     * @return ?TransactionInfo
     * If the method returns null, then this transaction is in the Pending or Dropped statuses
     */
    public function waitForReceipt(string $txHash, $timeout = 60, $interval = 1): ?TransactionInfo
    {
        $cb = EthCallback::getInstance();
        $t0 = time();
        while (true) {
            $this->eth->getTransactionReceipt($txHash, $cb);
            if ($cb->result) {
                break;
            }
            $t1 = time();
            if (($t1 - $t0) > $timeout) {
                break;
            }
            sleep($interval);
        }

        if (!$cb->result) {
            // Pending or Dropped transaction
            return null;
        }

        return new TransactionInfo((array)$cb->result);
    }

    /**
     * @param Credential $credential
     * @param RecipientWallet[] $toWallets
     * @return Transaction[]
     * @throws Exception
     */
    public function getPendingOrDroppedTransaction(Credential $credential, array $toWallets): array
    {
        $res = [];
        foreach ($toWallets as $i => $toWallet) {
            $transaction = TransactionCache::getCache($credential, $toWallet->getAddress());
            if (!$transaction) {
                continue;
            }
            $transactionHash = $transaction->getTransactionHash();
            if ($transactionHash !== null) {
                $transactionFromNet = $this->waitForReceipt($transactionHash->toString(), 0);
                if ($transactionFromNet === null) {
                    $res[] = $transaction;
                }
            }
        }
        return $res;
    }

    /**
     * @return UnitGwei[]
     * @throws Exception
     */
    public function getCurrentGasPrices(): array
    {
        try {
            $response = json_decode(
                file_get_contents('https://api.etherscan.io/api?module=gastracker&action=gasoracle&apikey=NM836RNQDC2KYWRD8CRG7TGU9YE9KTE6W8'),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $e) {
            var_dump($e);
            exit(0);
        }
        if ($response['message'] !== 'OK') {
            throw new Exception($response);
        }
        $result = $response['result'];
        return [
            self::GAS_LIMIT_LOW => new UnitGwei($result['SafeGasPrice']),
            self::GAS_LIMIT_MEDIUM => new UnitGwei($result['ProposeGasPrice']),
            self::GAS_LIMIT_HIGH => new UnitGwei($result['FastGasPrice'])
        ];
    }

    /**
     * Your wallet balance is currently $result ETH
     * @param $wallet
     * @return UnitWei
     */
    public function getWalletBalance($wallet): UnitWei
    {
        $cb = EthCallback::getInstance();
        $this->eth->getBalance($wallet, BlockNumber::latest()->toString(), $cb);
        return new UnitWei($cb->result->toString());
    }

    /**
     * @return Web3
     */
    public function getWeb3(): Web3
    {
        return $this->web3;
    }

    /**
     * @return Eth
     */
    public function getEth(): Eth
    {
        return $this->eth;
    }

}
