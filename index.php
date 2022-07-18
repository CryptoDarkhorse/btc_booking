<?php

use BitWasp\BitcoinLib\BIP32;
use BitWasp\BitcoinLib\RawTransaction;
use BitWasp\BitcoinLib\BitcoinLib;

require_once(__DIR__.'/vendor/autoload.php');
require_once(__DIR__.'/lib/Helper.php');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get saved hash value by txId
    if (!isset($_GET['txid'])) {
        die('Please provide transaction id');
    }

    echo loadDataFromBTCNetwork($_GET['txid']);
} else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Save hash value into BTC blockchain
    if (!isset($_POST['hash_val'])) {
        die('Please provide data to save');
    }

    echo saveToBTCNetwork($_POST['hash_val']);
} else {
    die('Unsupported request method');
}
