<?php


namespace App\Eth\Types;

class TransactionInfo
{
    private $blockHash = null;
    private $blockNumber = null;
    private $from = null;
    private $to = null;
    private $gas = null;
    private $gasPrice = null;
    private $gasUsed = null;
    private $input = null;
    private $nonce = null;
    private $transactionHash = null;
    private $transactionIndex = null;
    private $value = null;
    private $status = null;
    private $logs = [];

    public function __construct(array $response)
    {
        $this->blockHash = new BlockHash($response['blockHash']);
        $this->blockNumber = hexdec($response['blockNumber']);
        $this->from = new Address($response['from']);
        if (isset($response['to'])) {
            $this->to = new Address($response['to']);
        }
        if (isset($response['gas'])) {
            $this->gas = hexdec($response['gas']);
        }
        if (isset($response['gasPrice'])) {
            $this->gasPrice = new UnitWei(hexdec($response['gasPrice']));
        }
        if (isset($response['gasUsed'])) {
            $this->gasUsed = new UnitWei(hexdec($response['gasUsed']));
        }
        if (isset($response['nonce'])) {
            $this->nonce = hexdec($response['nonce']);
        }
        if (isset($response['status'])) {
            $this->status = hexdec($response['status']);
        }
        if (isset($response['input'])) {
            $this->input = $response['input'];
        }
        if (isset($response['value'])) {
            $this->value = new UnitWei(hexdec($response['value']));
        }
        if (isset($response['logs'])) {
            $this->logs = $response['logs'];
        }
        $this->transactionHash = new TransactionHash($response['transactionHash']);
        $this->transactionIndex = hexdec($response['transactionIndex']);
    }

    public function blockHash(): BlockHash
    {
        return $this->blockHash;
    }

    public function blockNumber(): int
    {
        return $this->blockNumber;
    }

    public function from(): Address
    {
        return $this->from;
    }

    public function to(): ?Address
    {
        return $this->to;
    }

    public function gas(): int
    {
        return $this->gas;
    }

    public function gasUsed(): UnitWei
    {
        return $this->gasUsed;
    }

    public function gasPrice(): UnitWei
    {
        return $this->gasPrice;
    }

    public function transactionHash(): TransactionHash
    {
        return $this->transactionHash;
    }

    public function input(): string
    {
        return $this->input;
    }

    public function nonce(): int
    {
        return $this->nonce;
    }

    public function transactionIndex(): int
    {
        return $this->transactionIndex;
    }

    public function value(): UnitWei
    {
        return $this->value;
    }

    public function getStatus(): int
    {
        return $this->status;
    }
}
