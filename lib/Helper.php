<?php

require_once(__DIR__.'/../vendor/autoload.php');

use BitWasp\BitcoinLib\RawTransaction;
use BitWasp\BitcoinLib\BitcoinLib;

const WALLET_ADDR = 'n1baM5BBPfqd71niUV4WoHjTKgJ2b5aG3Q';
const PRIVATE_KEY = 'cPK8ywJgket62a47Y5fm2zCS6E3cN7SqeitsvDsTpQFPeYJp9Ynq';
const TATUM_API_KEY = 'ba638a01-3a6d-4fa3-b15b-4f395d9b90a4';

const MAGIC = 'CKL2'; // chikuelo251

function createTransaction($inputs, $outputs, $magic_byte = null, $magic_p2sh_byte = null)
{
  $magic_byte = BitcoinLib::magicByte($magic_byte);
  $magic_p2sh_byte = BitcoinLib::magicP2SHByte($magic_p2sh_byte);

  $tx_array = array('version' => '1');

  // Inputs is the set of [txid/vout/scriptPubKey]
  $tx_array['vin'] = array();
  foreach ($inputs as $input) {
      if (!isset($input['txid']) || strlen($input['txid']) !== 64
          || !isset($input['vout']) || !is_numeric($input['vout'])
      ) {
          return false;
      }

      $tx_array['vin'][] = array('txid' => $input['txid'],
          'vout' => $input['vout'],
          'sequence' => (isset($input['sequence'])) ? $input['sequence'] : 4294967295,
          'scriptSig' => array('hex' => '')
      );
  }

  // Outputs is the set of [address/amount]
  $tx_array['vout'] = array();
  foreach ($outputs as $address => $value) {
      if (BitcoinLib::validate_address($address, $magic_byte, $magic_p2sh_byte)) {
        // send to address
        $decode_address = BitcoinLib::base58_decode($address);
        $version = substr($decode_address, 0, 2);
        $hash = substr($decode_address, 2, 40);
  
        if ($version == $magic_p2sh_byte) {
            // OP_HASH160 <scriptHash> OP_EQUAL
            $scriptPubKey = "a914{$hash}87";
        } else {
            // OP_DUP OP_HASH160 <pubKeyHash> OP_EQUALVERIFY OP_CHECKSIG
            $scriptPubKey = "76a914{$hash}88ac";
        }
      } else {
        // send to Script
        $scriptPubKey = $address;
      }
      $tx_array['vout'][] = array('value' => $value,
          'scriptPubKey' => array('hex' => $scriptPubKey)
      );
  }

  $tx_array['locktime'] = 0;

  return RawTransaction::encode($tx_array);
}

function callRPC($funcName, $params) {
    // build request body
    $body = "{
        \"jsonrpc\": \"2.0\",
        \"method\": \"$funcName\",
        \"params\":[\"$params\"], 
        \"id\": 2
    }";

    echo $body; echo "\n";

    $header[] = "Content-Type: application/json";
    $header[] = "Content-length: ".strlen($body);
    $header[] = "X-API-KEY: ".TATUM_API_KEY;

    $ch = curl_init();  
    curl_setopt($ch, CURLOPT_URL, "https://api-eu1.tatum.io/v3/blockchain/node/BTC");
    curl_setopt($ch, CURLOPT_POST, 1); // POST
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //curl_setopt($ch, CURLOPT_TIMEOUT, 1);
   
    $data = curl_exec($ch);      
    if (curl_errno($ch)) {
        die('Failed to call bitcoin RPC');
    } else {
        curl_close($ch);
        echo $data;
        return $data;
    }
}

function saveToBTCNetwork($data) {
    // concat magic bytes in front od data

    ////////////////////////////////////////////
    // TODO: get UTXO info by address

    $txid = '6a6dd5aeae19d4a915cffba3562fc69ccc705f31a0c4b1bfe3899807c3a1f052';
    $index = 0;

    // Set up inputs
    $inputs = array(array('txid' => $txid, 'vout' => $index));
    // Set up outputs
    $outputs = array(
        WALLET_ADDR => "0.00068888",
        '6a'.$data => "0",
    );

    ///////////////////////////////////////////////////////////////////////
    // Parameters for signing.
    // Create JSON inputs parameter
    // - These can come from bitcoind, or just knowledge of the txid/vout/scriptPubKey,
    //   and redeemScript if needed.
    $json_inputs = json_encode(
        array(
            array(
                'txid' => $txid,
                'vout' => $index,
                // OP_DUP OP_HASH160 push14bytes PkHash OP_EQUALVERIFY OP_CHECKSIG
                'scriptPubKey' => '76a914'.'dc424635b2a422502f72d9f12c2e0854a3cbfbe1'.'88ac')
        )
    );

    // Prepare key
    $wallet = array();
    RawTransaction::private_keys_to_wallet($wallet, array(PRIVATE_KEY), '6f');

    // Create raw transaction
    $raw_transaction = createTransaction($inputs, $outputs, '6f', 'c4');
    // print_r($raw_transaction);echo "\n";

    // Sign the transaction
    $signed = RawTransaction::sign($wallet, $raw_transaction, $json_inputs);
    print_r($signed); echo "\n";

    print_r($signed["hex"]); echo "\n";

    /////////////////////////////////////////////////
    // TODO: send raw transaction using bitcoin RPC
    //callRPC('sendrawtransaction', $signed["hex"]);
}

function loadDataFromBTCNetwork($txId) {

}