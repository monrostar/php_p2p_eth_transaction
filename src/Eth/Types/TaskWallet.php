<?php

namespace App\Eth\Types;

class TaskWallet
{
    /**
     * @var Credential
     */
    protected $credential;

    /**
     * @var RecipientWallet[]
     */
    protected $recipientWallets = [];

    /**
     * RecipientWallet constructor.
     * @param string $walletPrivateKey
     * @param RecipientWallet[] $recipientWallets
     */
    public function __construct(string $walletPrivateKey, array $recipientWallets)
    {
        $this->credential = Credential::fromKey($walletPrivateKey);
        $this->recipientWallets = $recipientWallets;

    }

    /**
     * @return Credential
     */
    public function getCredential(): Credential
    {
        return $this->credential;
    }

    /**
     * @return RecipientWallet[]
     */
    public function getRecipientWallets(): array
    {
        return $this->recipientWallets;
    }


}
