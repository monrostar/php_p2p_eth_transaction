<?php


namespace App\Eth\Types;


use DateTime;
use Exception;
use Web3\Utils;

class Transaction
{
    public const CHAIN_ID_MAINNET = 1;
    public const CHAIN_ID_RINKEBY = 4;

    public const GAS_PER_TRANSACTION = 21000;

    /**
     * @var Credential
     */
    protected $credential;
    /**
     * @var Address
     */
    protected $toWallet;
    /**
     * @var UnitEther
     */
    protected $value;
    /**
     * @var UnitGwei
     */
    protected $gasPrice;
    /**
     * @var int|null
     */
    protected $nonce;
    /**
     * @var mixed|string
     */
    protected $data;
    /**
     * @var int
     */
    protected $chainId;
    /**
     * @var TransactionHash
     */
    protected $transactionHash = null;
    /**
     * @var DateTime
     */
    protected $createdAt;

    /**
     * EthTransaction constructor.
     * @param Credential $credential
     * @param Address $toWallet
     * @param UnitEther $value
     * @param UnitGwei $gasPrice
     * @param int $chainId
     * @param int $nonce
     * @param string $data
     */
    public function __construct(Credential $credential,
                                Address $toWallet,
                                UnitEther $value,
                                UnitGwei $gasPrice,
                                int $chainId,
                                int $nonce,
                                $data = '')
    {
        $this->credential = $credential;
        $this->chainId = self::CHAIN_ID_MAINNET;
        if (in_array($chainId, [self::CHAIN_ID_MAINNET, self::CHAIN_ID_RINKEBY])) {
            $this->chainId = $chainId;
        }
        $this->toWallet = $toWallet;
        $this->value = $value;
        $this->gasPrice = $gasPrice;
        $this->nonce = $nonce;
        $this->data = $data;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getRaw(): array {
        return [
            'nonce' => $this->nonce,
            'gas' => self::GAS_PER_TRANSACTION,
            'gasPrice' => $this->gasPrice->toNumber(),
            'gasPrice_unit' => 'gwei',
            'from' => $this->credential->getAddress(),
            'to' => $this->toWallet->toString(),
            'value' => $this->value->toNumber(),
            'value_unit' => 'ether',
            'data' => $this->data,
            'chainId' => $this->chainId,
            'transactionHash' => !$this->transactionHash ? null : $this->transactionHash->toString(),
            'transactionUrl' => !$this->transactionHash ? null : self::getEthTxUrl($this->transactionHash->toString(), $this->isTestNetwork()),
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }

    public function isTestNetwork(): bool {
        return $this->chainId === $this::CHAIN_ID_RINKEBY;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getHexRaw(): array
    {
       return [
           'nonce' => Utils::toHex($this->nonce, true),
           'gas' => self::GAS_PER_TRANSACTION,
           'gasPrice' => '0x' . Utils::toWei((string)$this->gasPrice, 'gwei')->toHex(),
           'from' => $this->credential->getAddress(),
           'to' => $this->toWallet->toString(),
           'value' => '0x' . Utils::toHex(Utils::toWei((string)$this->value, 'ether'), true),
           'data' => '0x' . bin2hex($this->data),
           'chainId' => $this->chainId
       ];
    }

    public static function getEthTxUrl(string $txHash, $isTestNet = false): string
    {
        if (!$isTestNet) {
            $chainId = self::CHAIN_ID_MAINNET;
        } else {
            $chainId = self::CHAIN_ID_RINKEBY;
        }

        if ($chainId == self::CHAIN_ID_MAINNET) {
            return "https://etherscan.io/tx/{$txHash}";
        }
        return "https://rinkeby.etherscan.io/tx/{$txHash}";
    }

    /**
     * @param string $transactionHash
     * @return Transaction
     */
    public function setTransactionHash(string $transactionHash): Transaction
    {
        $this->transactionHash = new TransactionHash($transactionHash);
        return $this;
    }

    public function setCreatedAtNow(): void
    {
        $this->createdAt = new DateTime();
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    /**
     * @return TransactionHash
     */
    public function getTransactionHash(): ?TransactionHash
    {
        return $this->transactionHash;
    }

    /**
     * @return mixed|string
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * @return UnitGwei
     */
    public function getGasPrice(): UnitGwei
    {
        return $this->gasPrice;
    }

    /**
     * @param UnitGwei $gasPrice
     */
    public function setGasPrice(UnitGwei $gasPrice): void
    {
        $this->gasPrice = $gasPrice;
    }

    /**
     * @return int
     */
    public function getNonce(): int
    {
        return $this->nonce;
    }

    /**
     * @return int
     */
    public function getChainId(): int
    {
        return $this->chainId;
    }

    /**
     * @return UnitEther
     */
    public function getValue(): UnitEther
    {
        return $this->value;
    }

    /**
     * @return Address
     */
    public function getToWallet(): Address
    {
        return $this->toWallet;
    }

    /**
     * @return Credential
     */
    public function getCredential(): Credential
    {
        return $this->credential;
    }

    public function __toString() : string
    {
        return json_encode($this->getRaw());
    }

}
