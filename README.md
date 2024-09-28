# Circular PHP Class

The `Circular` class is a PHP implementation for interacting with the Circular Labs blockchain. It provides methods for performing various operations such as fetching wallet information, registering a wallet, fetching asset information, and sending transactions.

## Dependencies

This class requires the following dependencies:

- PHP 7.0 or higher
- Composer
- Elliptic PHP library

## CAVEATS

* Please note the following:
    * Error handling is very simplistic and should be tailored to your specific needs.
    * The fetch method in this PHP class does not behave exactly like the JavaScript fetch function especially regarding error handling.
      -- Consider replacing it with a more robust solution like Guzzle or CURL in a production environment.

## Installation

First, you need to install Composer and then run the following command to install the Elliptic library:

```bash
composer require simplito/elliptic-php
```

## Usage

Description of the methods available in the Circular class:

- `__construct()`: Initializes the elliptic curve cryptography (ECC) object.
- `setNAGKey($NAGKey)`: Sets the NAG key.
- `setNAGURL($NURL)`: Sets the NAG URL.
- `getWallet($blockchain, $address)`: Fetches the wallet information for a given blockchain and address.
- `registerWallet($blockchain, $privateKey)`: Registers a wallet on a given blockchain using a private key.
- `getAsset($blockchain, $name)`: Fetches asset information for a given blockchain and asset name.
- `getAssetSupply($blockchain, $name)`: Fetches the supply of a given asset on a specific blockchain.
- `getBlock($blockchain, $num)`: Fetches block information for a given blockchain and block number.
- `sendTransaction($id, $from, $to, $timestamp, $type, $payload, $nonce, $publicKey, $signature, $blockchain)`: Sends a transaction on a given blockchain.

## Example
Example of how to use the Circular class:

```php
<?php

// Include the circular sdk
require_once __DIR__ . '/vendor/autoload.php'; 
require_once 'lib/CIRCULAR.php';

$circular = new Circular();

// Set the NAG key and URL
$circular->setNAGKey('your_nag_key');
$circular->setNAGURL('https://nag.circularlabs.io/NAG.php?cep=');

// Register a wallet
$blockchain = 'your_blockchain';
$publicKey = 'your_public_key';
$response = $circular->registerWallet($blockchain, $publicKey);

 if ($response) {
    echo "Wallet registered successfully.\n";
} else {
    echo "Failed to register wallet.\n";
}

print_r($response);

// Get wallet information
$address = 'your_address';
$walletInfo = $circular->getWallet($blockchain, $address);

echo "Wallet Information: \n";
print_r($walletInfo);
```
Note: Replace 'your_nag_key', 'your_blockchain', 'your_public_key', and 'your_address' with your actual values.


## Disclaimer

This class is provided as-is, and it is up to the user to ensure that it is used correctly and securely. Always remember to keep your private keys secure and never share them with anyone. Always test your code thoroughly before deploying it in a production environment.
