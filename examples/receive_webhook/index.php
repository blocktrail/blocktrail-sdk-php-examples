<?php

use Blocktrail\SDK\BlocktrailSDK;

require_once __DIR__ . "/../../vendor/autoload.php";

/*
 * get JSON post data by reading from stdin
 *
 * note; before PHP 5.6 you can only read from `php://input` once
 *       so if you have a framework/CMS than you need to check if it doesn't already read it before you
 *       and if it does you need to get it from the framework instead of directly from `php://input`
 */
$postData = file_get_contents("php://input");
if (!$postData) {
    throw new \Exception("Failed to get POST body");
}

$postData = json_decode($postData, true);
if (!$postData) {
    throw new \Exception("Failed to decode JSON from POST body");
}

$network = $postData['network']; // BTC or tBTC
$eventType = $postData['event_type']; // address-transactions or block

if ($eventType == 'address-transactions') {
    $addresses = $postData['addresses']; // list of addresses you're subscribed to and their balance change
    $txData = $postData['data']; // transasaction data, same structure as the data API transaction endpoint

    echo "Received webhook for transaction [{$txData['hash']}] \n";

    /*
     * you can loop over the addresses that you were subscribed to
     *
     * note; keep in mind that it's possible there are multiple,
     *       for example when it was an outgoing transaction there's a good chance that you'll send BTC from multiple addresses
     */
    foreach ($addresses as $address => $balanceChange) {
        if ($balanceChange > 0) {
            $balanceChangeInBtc = BlocktrailSDK::toBTC($balanceChange);
            echo "Address [{$address}] has received {$balanceChange} Satoshis, which is {$balanceChangeInBtc} BTC \n";
        } else if ($balanceChange < 0) {
            $balanceChangeInBtc = BlocktrailSDK::toBTC($balanceChange);
            echo "Address [{$address}] has sent {$balanceChange} Satoshis, which is {$balanceChangeInBtc} BTC \n";
        } else {
            echo "Address [{$address}] was part of a transaction, but it's balance remained the same \n";
        }
    }

} else if ($eventType == 'block') {
    $blockData = $postData['data']; // block data, same structure as the data API block endpoint

    echo "Received webhook for block [{$blockData['hash']}] with height [{$blockData['height']}] \n";

} else {
    throw new \Exception("ERROR; received unknown webhook event type [{$eventType}]");
}
