<?php

declare(strict_types=1);

namespace App;

use App\Eth\EthService;
use App\Eth\Types\Address;
use App\Eth\Types\Transaction;
use App\Eth\Types\UnitEther;
use Exception;

require __DIR__ . '/vendor/autoload.php';

class Constants
{
    public const IS_TEST = true;
}

$uri = $_SERVER['REQUEST_URI'];

set_time_limit(180);

$ethService = EthService::getInstance(Constants::IS_TEST);
try {
    $gasCurrentPrices = $ethService->getCurrentGasPrices();
    $tx = postSendTransaction();
} catch (Exception $e) {
    $err = $e;
}

/**
 * @return Eth\Types\Transaction|null
 * @throws Eth\Exceptions\InvalidBalanceException
 * @throws Exception
 */
function postSendTransaction(): ?Eth\Types\Transaction
{
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method !== 'POST') {
        return null;
    }
    $ethService = EthService::getInstance(Constants::IS_TEST);
    $privateKey = (string)$_POST['walletPrivateKey'];
    $walletDestination = new Address((string)$_POST['walletDestination']);
    $amount = (float)$_POST['amount'];
    $unit = strtolower((string)$_POST['unit']);
    $gasPrice = $ethService->getCurrentGasPrices()[$_POST['gasPrice'] ?? EthService::GAS_LIMIT_LOW];
    return $ethService->sendFromKey($privateKey, $walletDestination, new UnitEther($amount), $gasPrice);
}

?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport"
    content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Send</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
</head>
<body>
<div class="container mt-5">
  <div class="row justify-content-md-center">
    <div class="col-6">
      <form method="post" action="<?= $uri ?>">
        <div class="mb-3">
          <label for="walletPrivateKey" class="form-label">Private Key</label>
          <input required type="password" class="form-control" id="walletPrivateKey" name="walletPrivateKey">
          <div class="form-text">Your private key.</div>
        </div>
        <div class="mb-3">
          <label for="walletDestination" class="form-label">Wallet destination</label>
          <input required type="text" class="form-control" name="walletDestination" id="walletDestination">
          <div id="emailHelp" class="form-text">Wallet destination.</div>
        </div>
        <div class="input-group mb-3">
          <span class="input-group-text">Amount Eth</span>
          <input required name="amount" type="text" aria-label="amount" class="form-control">
          <select required class="form-select form-control" name="unit" aria-label="unit">
            <option value="ether" selected>ether</option>
          </select>
        </div>
          <?php if (isset($gasCurrentPrices)): ?>
            <div class="input-group mb-3">
              <span class="input-group-text">Select Gas Price in Gwei</span>
              <select required class="form-select form-control" name="gasPrice" aria-label="Default select example">
                <option value="<?= EthService::GAS_LIMIT_LOW ?>" selected>
                  Low <?= $gasCurrentPrices[EthService::GAS_LIMIT_LOW] ?>
                </option>
                <option value="<?= EthService::GAS_LIMIT_MEDIUM ?>">
                  Medium <?= $gasCurrentPrices[EthService::GAS_LIMIT_MEDIUM] ?>
                </option>
                <option value="<?= EthService::GAS_LIMIT_HIGH ?>">
                  High <?= $gasCurrentPrices[EthService::GAS_LIMIT_HIGH] ?>
                </option>
              </select>
            </div>
          <?php endif; ?>
        <button type="submit" class="btn btn-primary">Submit</button>
      </form>
    </div>
    <div class="row justify-content-md-center">
      <div class="col-6 mt-3">
          <?php if (isset($tx)): ?>
            <div class="alert alert-success" role="alert">
              <a href="<?= Transaction::getEthTxUrl($tx->getTransactionHash()->toString(), Constants::IS_TEST) ?>"
                style="font-size: x-small;"><?= $tx->getTransactionHash() ?></a>
              <br>
              <small class="text-muted">Note: please allow for 30 seconds before transaction appears on
                Etherscan</small>
            </div>
          <?php endif; ?>
      </div>
    </div>
    <div class="row justify-content-md-center">
      <div class="col-6 mt-3">
          <?php if (isset($err)): ?>
            <div class="alert alert-danger" role="alert">
                <?= $err->getMessage() ?>
            </div>
          <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-ygbV9kiqUc6oa4msXn9868pTtWMgiQaeYH7/t7LECLbyPA2x65Kgf80OJFdroafW" crossorigin="anonymous"></script>
</body>
</html>
