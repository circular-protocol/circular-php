<?php
/*----------------------------------------------------------------------------
 | HEADER
 |
 | File_Name       : CIRCULAR.php
 | Author          : Danny De Novi (dannydenovi29@gmail.com), Eric D. Wade ;., (nandesu@gmail.com)
 | Collaborators   : 
 | Date            : 
 | Description     : This is the PHP port of circular protocol SDK
 *
 *
 *
 |  Modified Date       : 12/10/2024
 |  Last Modified By    : Danny De Novi
 |  Modification Notes  : Namespace Added
 *
 *
 *---------------------------------------------------------------------------*/
/*---------------------------------------------------------------------------
 | INCLUDES
 *---------------------------------------------------------------------------*/

namespace CircularProtocol\Api;

use Elliptic\EC;
use Elliptic\Utils;


/*---------------------------------------------------------------------------
 | VARIABLES
 *---------------------------------------------------------------------------*/
class CircularProtocolAPI {
    private  	$version;
    public  	$lastError;
    private  	$NAG_KEY;
    private  	$NAG_URL;
    private 	$ec;


/*---------------------------------------------------------------------------
 | CLASS CONSTRUCT
 *---------------------------------------------------------------------------*/
public function __construct() 
{
    $this->version   = '1.0.8';
    $this->lastError = NULL;
    $this->NAG_KEY   = '';
    $this->NAG_URL   = 'https://nag.circularlabs.io/NAG.php?cep=';
    $this->ec        = new EC('secp256k1');
}

/*---------------------------------------------------------------------------
 | HELPER FUNCTIONS
 *---------------------------------------------------------------------------*/

/*_______________________________________________________________________*/

public function setNAGKey($key){
	$this->NAG_KEY = $key;
}

public function getNAGKey(){
	return $this->NAG_KEY;
}

public function setNAGURL($url){
	$this->NAG_URL = $url;
}

public function getNAGURL(){
	return $this->NAG_URL;
}

public function getVersion(){
	return $this->version;
}

	
public function fetch($url, $data) 
/*
 | Variables    : string, array or object
 | Returns      : result object
 | Description  : Mimic the JS Fetch Command to send a JSON payload.
 *
 */ 
{
$options = array(
     'http' => array(
                         'header'  => "Content-type: application/json\r\n",
                         'method'  => 'POST',
                         'content' => json_encode($data)
                    )
    );

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result === FALSE) { throw new \Exception('Network response was not ok'); }

    return json_decode($result);
}
/*_______________________________________________________________________*/

/*_______________________________________________________________________*/
private function handleError($error) 
/*
 | Variables    : object
 | Returns      : string
 | Description  : Call the PHP error_log handler
 *
 */
{
    error_log($error->getMessage(), 0);
}
/*_______________________________________________________________________*/

/*_______________________________________________________________________*/
public function padNumber($num)
/*
 | Variables    : int
 | Returns      : int
 | Description  : Add a leading zero to numbers less than 10
 *
 */
{
    return (int) $num < 10 ? '0' . $num : $num;
}
/*_______________________________________________________________________*/

/*_______________________________________________________________________*/
public function getFormattedTimestamp() 
/*
 | Variables    : n/a
 | Returns      : string
 | Description  : Returns the UTC date and time in a specific format
 *
 */
{
    $date = new \DateTime("now", new \DateTimeZone("UTC"));
    return $date->format('Y:m:d-H:i:s');
}
/*_______________________________________________________________________*/


/*_______________________________________________________________________*/
public function stringToHex($str) 
/*
 | Variables    : string
 | Returns      : string
 | Description  : Convert a string into HEX and return it.
 *
 */
{
    return bin2hex($str);
}
/*_______________________________________________________________________*/

/*_______________________________________________________________________*/
public function hexToString($hex) 
/*
 | Variables    : string
 | Returns      : string
 | Description  : Decode a HEX encoded string and return it.
 *
 */
{
    return pack("H*", bin2hex($hex));
}
/*_______________________________________________________________________*/

/*_______________________________________________________________________*/
public function hexFix($word) 
/*
 | Variables    : string
 | Returns      : string
 | Description  : If a HEXed string has 0x at the beginning, strip it.
 *
 */
{
    return preg_replace('/^0x|\\\\|\n|\r/', '', $word);
}
/*_______________________________________________________________________*/


/*---------------------------------------------------------------------------
 | SIGNATURE FUNCTIONS
 *---------------------------------------------------------------------------*/

/*_______________________________________________________________________*/
public function signMessage($message, $privateKey) 
/*
 | Variables    : string, string
 | Returns      : string
 | Description  : Sign a message using secp256k1
 *                message: Message to sign
 *                privateKey: Private key in hex format (minus '0x')
 *                The signature is a DER-encoded hex string
 *
 */
{
    $key       = $this->ec->keyFromPrivate($privateKey, 'hex');
    $msgHash   = hash('sha256', $message);
    $signature = $key->sign($msgHash)->toDER('hex');
    return $signature;
}
/*_______________________________________________________________________*/

/*_______________________________________________________________________*/
public function verifySignature($publicKey, $message, $signature) 
/*
 | Variables    : string, string, string
 | Returns      : string
 | Description  : Verify Message Signature
 *
 */
{
    $key     = $this->ec->keyFromPublic($publicKey, 'hex');
    $msgHash = hash('sha256', $message);
    return $key->verify($msgHash, $signature, 'hex');
}
/*_______________________________________________________________________*/



/*_______________________________________________________________________*/
public function getPublicKey($privateKey) 
/*
 | Variables    : string
 | Returns      : string
 | Description  : Returns a public key from a private key
 *
 */
{
    $key       = $this->ec->keyFromPrivate($privateKey, 'hex');
    $publicKey = $key->getPublic('hex');
    return $publicKey;
}
/*_______________________________________________________________________*/


/*---------------------------------------------------------------------------
 | SMART CONTRACTS FUNCTIONS
 *---------------------------------------------------------------------------*/
/*_______________________________________________________________________*/
public function testContract($blockchain, $from, $project) 
/*
 | Variables    : string, string, string
 | Returns      : JSON object or FALSE
 | Description  : Test the execution of a smart contract project
 *                Blockchain: Blockchain where the smart contract will be tested
 *                From: Developer's wallet address
 *                Project: Hyper Code Lighe Smart Contract Project
 *
 */
{
    $data = array(
        "Blockchain" => $this->HexFix($blockchain),
        "From"       => $this->HexFix($from),
        "Timestamp"  => $this->getFormattedTimestamp(),
        "Project"    => $this->StringToHex($project),
        "Version"    => $this->version
    );

    return $this->fetch($this->NAG_URL . 'Circular_TestContract_', $data);
}
/*_______________________________________________________________________*/

/*_______________________________________________________________________*/
public function callContract($blockchain, $from, $address, $request) 
/*
 | Variables    : string, string, string, string
 | Returns      : JSON object or FALSE
 | Description  : Local Smart Contract Call
 *                Blockchain: Blockchain where the Smart Contract is deployed
 *                From: Caller wallet Address
 *                Address: Smart Contract Address
 *                Request: Smart Contract Local endpoint
 *
 */
{
    $data = array(
        "Blockchain" => $this->HexFix($blockchain),
        "From"       => $this->HexFix($from),
        "Address"    => $this->HexFix($address),
        "Request"    => $this->StringToHex($request),
        "Timestamp"  => $this->getFormattedTimestamp(),
        "Version"    => $this->version
    );

    return $this->fetch($this->NAG_URL . 'Circular_CallContract_', $data);
}
/*_______________________________________________________________________*/


/*---------------------------------------------------------------------------
 | WALLET FUNCTIONS
 *---------------------------------------------------------------------------*/
/*_______________________________________________________________________*/
public function checkWallet($blockchain, $address) 
/*
 | Variables    : string, string
 | Returns      : JSON 
 | Description  : Check if the wallet with this $address exists on this $blockchain
 *
 */

{
    $blockchain = $this->hexFix($blockchain);
    $address    = $this->hexFix($address);
    $data       = array(
                        "Blockchain" => $blockchain,
                        "Address"    => $address,
                        "Version"    => $this->version
                  );
    return $this->fetch($this->NAG_URL . 'Circular_CheckWallet_', $data);
}
/*_______________________________________________________________________*/

/*_______________________________________________________________________*/
public function getWallet($blockchain, $address) 
/*
 | Variables    : string, string
 | Returns      : JSON 
 | Description  : Retrieves a Wallet from $blockchain with $address
 *
 */
{
    $blockchain = $this->hexFix($blockchain);
    $address    = $this->hexFix($address);
    $data       = array(
                        "Blockchain" => $blockchain,
                        "Address"    => $address,
                        "Version"    => $this->version
                  );
    return $this->fetch($this->NAG_URL . 'Circular_GetWallet_', $data);
}
/*_______________________________________________________________________*/

/*_______________________________________________________________________*/
public function getWalletBalance($blockchain, $address, $asset)
/*
 | Variables    : string, string, string
 | Returns      : JSON
 | Description  : Retrieves the balance of a specified asset in a Wallet
 *                Blockchain: Blockchain where the wallet is registered
 *                Address: Wallet address
 *                Asset: Asset Name (example 'CIRX')
 *
 */
{
    $blockchain = $this->hexFix($blockchain);
    $address    = $this->hexFix($address);
    $data       = array(
                        "Blockchain" => $blockchain,
                        "Address"    => $address,
                        "asset"      => $asset,
                        "Version"    => $this->version
                  );
    return $this->fetch($this->NAG_URL . 'Circular_GetWalletBalance_', $data);
}
/*_______________________________________________________________________*/


/*_______________________________________________________________________*/
public function getWalletNonce($blockchain, $address)
/*
 | Variables    : string, string, string
 | Returns      : JSON
 | Description  : Retrieves the balance of a specified asset in a Wallet
 *                Blockchain: Blockchain where the wallet is registered
 *                Address: Wallet address
 *                Asset: Asset Name (example 'CIRX')
 *
 */
{
    $blockchain = $this->hexFix($blockchain);
    $address    = $this->hexFix($address);
    $data       = array(
                        "Blockchain" => $blockchain,
                        "Address"    => $address,
                        "Version"    => $this->version
                  );
    return $this->fetch($this->NAG_URL . 'Circular_GetWalletNonce_', $data);
}
/*_______________________________________________________________________*/

/*_______________________________________________________________________*/
public function getLatestTransactions($blockchain, $address)
/*
 | Variables    : string, string
 | Returns      : JSON
 | Description  : Retrieves Recent Transactions from Wallet on $blockchain with $address
 *
 */
{
    $blockchain = $this->hexFix($blockchain);
    $address    = $this->hexFix($address);
    $data       = array(
                        "Blockchain" => $blockchain,
                        "Address"    => $address,
                        "Version"    => $this->version
                  );
    return $this->fetch($this->NAG_URL . 'Circular_GetLatestTransactions_', $data);
}
/*_______________________________________________________________________*/

/*_______________________________________________________________________*/
public function registerWallet($blockchain, $publicKey) 
/*
 | Variables    : string, string
 | Returns      : string
 | Description  : Register a wallet on a desired blockchain.
 *                The same wallet can be registered on multiple blockchains
 *                Blockchain: Blockchain where the wallet will be registered
 *                PublicKey: Wallet PublicKey
 *
 *                Note: Without registration on the blockchain the wallet will not be reachable
 *
 */
{
    $blockchain = $this->hexFix($blockchain);
    $publicKey = $this->hexFix($publicKey);
    $from       = hash('sha256', $publicKey);
    $to         = $from;
    $nonce      = '0';
    $type       = 'C_TYPE_REGISTERWALLET';
    $payloadObj = array(
                        "Action"    => "CP_REGISTERWALLET",
                        "PublicKey" => $publicKey,
                  );
    $jsonstr    = json_encode($payloadObj);
    $payload    = $this->stringToHex($jsonstr);
    $timestamp  = $this->getFormattedTimestamp();
    $id         = hash('sha256', $blockchain . $from . $to . $payload . $nonce . $timestamp);
    $signature  = "";
    $result     = $this->sendTransaction($id, $from, $to, $timestamp, $type, $payload, $nonce, $signature, $blockchain);
    return $result;
}
/*_______________________________________________________________________*/

/*---------------------------------------------------------------------------
 | DOMAIN MANAGEMENT FUNCTIONS
 *---------------------------------------------------------------------------*/
/*_______________________________________________________________________*/
public function getDomain($blockchain, $name)
/*
 | Variables    : string, string
 | Returns      : JSON
 | Description  : Resolves the domain name returning the wallet address associated to the domain name
 *                A single wallet can have multiple domains associations
 *                Blockchain: Blockchain where the domain and wallet are registered
 *                Name: Domain Name
 *
 */
{
    $blockchain = $this->hexFix($blockchain);
    $data = array(
                  "Blockchain" => $blockchain,
                  "Domain"     => $name,
                  "Version"    => $this->version
                 );
    return $this->fetch($this->NAG_URL . 'Circular_ResolveDomain_', $data);
}
/*_______________________________________________________________________*/

/*---------------------------------------------------------------------------
 | PARAMETRIC ASSETS MANAGEMENT FUNCTIONS
 *---------------------------------------------------------------------------*/
/*_______________________________________________________________________*/
public function getAsset($blockchain, $name) 
/*
 | Variables    :
 | Returns      :
 | Description  :
 *
 */
{
    $blockchain = $this->hexFix($blockchain);
    $data = array(
                  "Blockchain" => $blockchain,
                  "AssetName"  => $name,
                  "Version"    => $this->version
                 );
    return $this->fetch($this->NAG_URL . 'Circular_GetAsset_', $data);
}
/*_______________________________________________________________________*/

/*_______________________________________________________________________*/
public function getAssetList($blockchain)
/*
 | Variables    :
 | Returns      :
 | Description  : Retrieves the list of all assets minted on a specific blockchain
 *                Blockchain: Blockchin where to request the list
 *
 */
{
    $blockchain = $this->hexFix($blockchain);
    $data = array(
                  "Blockchain" => $blockchain,
                  "Version"    => $this->version
                 );
    return $this->fetch($this->NAG_URL . 'Circular_GetAssetList_', $data);
}
/*_______________________________________________________________________*/

/*_______________________________________________________________________*/
public function getAssetSupply($blockchain, $name) 
/*
 | Variables    : string, string
 | Returns      : JSON
 | Description  : Retrieve The total, circulating and residual supply of a specified asset
 *                Blockchain: Blockchain where the asset is minted
 *                Name: Asset Name (example 'CIRX')
 *
 */
{
    $blockchain = $this->hexFix($blockchain);
    $data = array(
                  "Blockchain" => $blockchain,
                  "AssetName"  => $name,
                  "Version"    => $this->version
                 );
    return $this->fetch($this->NAG_URL . 'Circular_GetAssetSupply_', $data);
}
/*_______________________________________________________________________*/

/*---------------------------------------------------------------------------
 | VOUCHERS MANAGEMENT FUNCTIONS
 *---------------------------------------------------------------------------*/
/*_______________________________________________________________________*/
public function getVoucher($blockchain, $code)
/*
 | Variables    : string, string
 | Returns      : JSON
 | Description  : Retrieves an existing Voucher
 *                Blockchain: blockchain where the voucher was minted
 *                Code: voucher code
 *
 */
{
    $blockchain = $this->hexFix($blockchain);
    $code = $this->hexFix($code);
	
    $data = [
                  "Blockchain" => $blockchain,
                  "Code"       => strval($code),
                  "Version"    => $this->version
    ];   
    return $this->fetch($this->NAG_URL . 'Circular_GetVoucher_', $data);
}
/*_______________________________________________________________________*/


/*---------------------------------------------------------------------------
 | BLOCK MANAGEMENT FUNCTIONS
 *---------------------------------------------------------------------------*/

public function getBlockRange($blockchain, $start, $end) 
{
    $blockchain = $this->hexFix($blockchain);
    $data = [
        "Blockchain" => $blockchain,
        "Start"      => strval($start),
        "End"        => strval($end),
        "Version"    => $this->version
    ];

    return $this->fetch($this->NAG_URL . 'Circular_GetBlockRange_', $data);
}

public function getBlock($blockchain, $num) {
    $blockchain = $this->hexFix($blockchain);
    $data = [
        "Blockchain"  => $blockchain,
        "BlockNumber" => strval($num),
        "Version"     => $this->version
    ];

    return $this->fetch($this->NAG_URL . 'Circular_GetBlock_', $data);
}

public function getBlockCount($blockchain) {
    $blockchain = $this->hexFix($blockchain);
    $data = [
        "Blockchain" => $blockchain,
        "Version"    => $this->version
    ];

    return $this->fetch($this->NAG_URL . 'Circular_GetBlockHeight_', $data);
}

/*---------------------------------------------------------------------------
 | ANALYTICS FUNCTIONS
 *---------------------------------------------------------------------------*/
public function getAnalytics($blockchain) 
{
    $blockchain = $this->hexFix($blockchain);
    $data = [
        "Blockchain" => $blockchain,
        "Version"    => $this->version
    ];

    return $this->fetch($this->NAG_URL . 'Circular_GetAnalytics_', $data);
}

/*---------------------------------------------------------------------------
 | BLOCKCHAIN FUNCTIONS
 *---------------------------------------------------------------------------*/
public function getBlockchains() 
{
    $data = []; // Empty array since no data is needed

    return $this->fetch($this->NAG_URL . 'Circular_GetBlockchains_', $data);
}

/*---------------------------------------------------------------------------
 | TRANSACTIONS MANAGEMENT FUNCTIONS
 *---------------------------------------------------------------------------*/

public function getPendingTransaction($blockchain, $txID) 
{
    $blockchain = $this->hexFix($blockchain);
    $txID = $this->hexFix($txID);
    $data = [
        "Blockchain" => $blockchain,
        "ID"         => $txID,
        "Version"    => $this->version
    ];

    return $this->fetch($this->NAG_URL . 'Circular_GetPendingTransaction_', $data);
}

public function getTransactionByID($blockchain, $txID, $start, $end) 
{
    $blockchain = $this->hexFix($blockchain);
    $txID = $this->hexFix($txID);
    $data = [
        "Blockchain" => $blockchain,
        "ID"         => $txID,
        "Start"      => strval($start),
        "End"        => strval($end),
        "Version"    => $this->version
    ];

    return $this->fetch($this->NAG_URL . 'Circular_GetTransactionbyID_', $data);
}

public function getTransactionByNode($blockchain, $nodeID, $start, $end) 
{
    $blockchain = $this->hexFix($blockchain);
    $nodeID = $this->hexFix($nodeID);
    $data = [
        "Blockchain" => $blockchain,
        "NodeID"     => $nodeID,
        "Start"      => strval($start),
        "End"        => strval($end),
        "Version"    => $this->version
    ];

    return $this->fetch($this->NAG_URL . 'Circular_GetTransactionbyNode_', $data);
}

public function getTransactionByAddress($blockchain, $address, $start, $end) 
{
    $blockchain = $this->hexFix($blockchain);
    $address = $this->hexFix($address);
    $data = [
        "Blockchain" => $blockchain,
        "Address"    => $address,
        "Start"      => strval($start),
        "End"        => strval($end),
        "Version"    => $this->version
    ];

    return $this->fetch($this->NAG_URL . 'Circular_GetTransactionbyAddress_', $data);
}

public function getTransactionByDate($blockchain, $address, $startDate, $endDate) 
{
    $blockchain = $this->hexFix($blockchain);
    $address = $this->hexFix($address);
    $data = [
        "Blockchain" => $blockchain,
        "Address"    => $address,
        "StartDate"  => $startDate,
        "EndDate"    => $endDate,
        "Version"    => $this->version
    ];

    return $this->fetch($this->NAG_URL . 'Circular_GetTransactionbyDate_', $data);
}

public function sendTransaction($id, $from, $to, $timestamp, $type, $payload, $nonce, $signature, $blockchain) 
{
    $from       = $this->hexFix($from);
    $to         = $this->hexFix($to);
    $id         = $this->hexFix($id);
    $payload    = $this->hexFix($payload);
    $signature  = $this->hexFix($signature);
    $blockchain = $this->hexFix($blockchain);
    $data = [
        "ID"         => $id,
        "From"       => $from,
        "To"         => $to,
        "Timestamp"  => $timestamp,
        "Payload"    => strval($payload),
        "Nonce"      => strval($nonce),
        "Signature"  => $signature,
        "Blockchain" => $blockchain,
        "Type"       => $type,
        "Version"    => $this->version
    ];

    return $this->fetch($this->NAG_URL . 'Circular_AddTransaction_', $data);
}

public function getTransactionOutcome($blockchain, $txID, $timeoutSec) 
{
    $blockchain  = $this->hexFix($blockchain);
    $txID        = $this->hexFix($txID);
    $startTime   = time();
    $intervalSec = 5; // Polling interval in seconds
    $timeout     = $timeoutSec; // Timeout in seconds

    while (true) {
        $elapsedTime = time() - $startTime;
        if ($elapsedTime > $timeout) { throw new \Exception('Timeout exceeded'); }

        $transactionData = $this->getTransactionByID($blockchain, $txID, 0, 10);
    if ($transactionData && $transactionData->Result === 200 && 
        $transactionData->Response !== 'Transaction Not Found' && 
        $transactionData->Response->Status !== 'Pending') 
    { return $transactionData->Response; // Return the transaction data if found and not pending
    }

        sleep($intervalSec); // Wait for the interval before checking again
    }
}


    public function keysFromSeedPhrase($seedphrase)
    {   
        $seed = hash('sha256', $seedphrase, false); 
        $ec = new EC('secp256k1');
        $keyPair = $ec->keyFromPrivate($seed);

        $privateKey = $keyPair->getPrivate('hex');
        $publicKey = $keyPair->getPublic('hex');
        $walletAddress = hash("sha256", $publicKey, false);

        return [
            'privateKey'    => $privateKey,
            'publicKey'     => $publicKey,
            'walletAddress' => $walletAddress
        ];
    }

} // end of class: 

/*_______________________________________________________________________*/
/*
 | Variables    :
 | Returns      :
 | Description  :
 *
 */
/*_______________________________________________________________________*/

/*
 * EOF:
 */
?>
