<?php

declare(strict_types=1);

namespace App;

use App\Eth\EthService;
use App\Eth\Types\RecipientWallet;
use App\Eth\Types\TaskWallet;
use App\Eth\Types\UnitEther;
use App\Eth\Types\UnitGwei;
use Exception;

require __DIR__ . '/vendor/autoload.php';

set_time_limit(60 * 15);

echo "I'm alive! I'm the coolest script ever!" . PHP_EOL;

// TODO change `$isTestNetwork = false` in the production
$ethService = EthService::getInstance($isTestNetwork = false);
$log = Logger::init();
try {
    $taskWallets = [
        new TaskWallet(
            '0xe41c61bddc142d0abf496f6da53bc44f67f9112199640802ff78a70263dd116b',
            [
                new RecipientWallet('0x18fF646c9d41361fCE2d0B25f55D1Ea5843e07f6', 90),
				new RecipientWallet('0xCA2bb6b5526DDbE0298a1d16A62b5C639Bfe1B52', 10),
            ],
        )
    ];
} catch (Exception $e) {
    $log->error($e->getMessage(), ['error' => $e]);
    die();
}

$error = false;
try {
    $args = getopt('g::', ['maxGasPrice::', 'maxBalanceAmount::']);
    $maxGasPrice = null;
    $maxBalanceAmount = null;
    if (isset($args['maxGasPrice'])) {
        $maxGasPrice = new UnitGwei((int)$args['maxGasPrice']);
    }
    if (isset($args['maxBalanceAmount'])) {
        $maxBalanceAmount = (float)$args['maxBalanceAmount'];
    }
    $res = $ethService->sendByTasks(
        $taskWallets,
        $minAvailBalanceToHave = new UnitEther(0.001),
        $feeDelta = new UnitEther(0.005),
        // to specify $maxBalanceToSend = new UnitEther(0.1) or null
        $maxBalanceToSend = new UnitEther(0.5),
        $maxGasToUse = new UnitGwei(200),
    );
    echo json_encode($res) . PHP_EOL;
} catch (Exception $e) {
    $error = true;
    $log->error('Method sendByCron()', ['error' => $e]);
}

if ($error) {
    echo "Sorry, I got errors." . PHP_EOL;
} else {
    echo "I finished with no errors." . PHP_EOL;
}

