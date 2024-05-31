<?php
/*----------------------------------------------------------------------------
 | HEADER
 |
 | File_Name       : CIRCULAR.php
 | Author          : Eric D. Wade ;., (nandesu@gmail.com)
 | Date            : 
 | Description     : This is the PHP port of circular protocal SDK
 *
 *
 *
 |  Modified Date       :
 |  Last Modified By    :
 |  Modification Notes  :
 *
 *
 *---------------------------------------------------------------------------*/

/*---------------------------------------------------------------------------
 | INCLUDES
 *---------------------------------------------------------------------------*/
use Elliptic\EC;

/*---------------------------------------------------------------------------
 | VARIABLES
 *---------------------------------------------------------------------------*/
class Circular {
    public  $version;
    public  $lastError;
    public  $NAG_KEY;
    public  $NAG_URL;
    private $ec;


/*---------------------------------------------------------------------------
 | CLASS CONSTRUCT
 *---------------------------------------------------------------------------*/
public function __construct() 
{
    $this->version   = '1.0.7';
    $this->lastError = NULL;
    $this->NAG_KEY   = '';
    $this->NAG_URL   = 'https://nag.circularlabs.io/NAG.php?cep=';
    $this->ec        = new EC('secp256k1');
}

/*---------------------------------------------------------------------------
 | HELPER FUNCTIONS
 *---------------------------------------------------------------------------*/

/*_______________________________________________________________________*/
private function fetch($url, $data) 
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
    $result = file_get_contents(NAG_URL . 'Circular_CallContract_', false, $context);

    if ($result === FALSE) { throw new Exception('Network response was not ok'); }

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
private function padNumber($num)
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
private function getFormattedTimestamp() 
/*
 | Variables    : n/a
 | Returns      : string
 | Description  : Returns the UTC date and time in a specific format
 *
 */
{
    $date = new DateTime("now", new DateTimeZone("UTC"));
    return $date->format('Y:m:d-H:i:s');
}
/*_______________________________________________________________________*/


/*_______________________________________________________________________*/
private function stringToHex($str) 
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
private function hexToString($hex) 
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
private function hexFix($word) 
/*
 | Variables    : string
 | Returns      : string
 | Description  : If a HEXed string has 0x at the beginning, strip it.
 *
 */
{
    return preg_replace('/^0x/', '', $word);
}
/*_______________________________________________________________________*/


/*---------------------------------------------------------------------------
 | SIGNATURE FUNCTIONS
 *---------------------------------------------------------------------------*/

/*_______________________________________________________________________*/
private function signMessage($message, $privateKey) 
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
private function verifySignature($publicKey, $message, $signature) 
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
private function getPublicKey($privateKey) 
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
 | NAG FUNCTIONS
 *---------------------------------------------------------------------------*/

/*_______________________________________________________________________*/
public function setNAGKey($NAGKey) 
/*
 | Variables    : string
 | Returns      : n/a
 | Description  : Set the NAG_KEY
 *
 */
{
    $this->NAG_KEY = $NAGKey;
}
/*_______________________________________________________________________*/

/*_______________________________________________________________________*/
public function setNAGURL($NURL='https://nag.circularlabs.io/NAG.php?cep=') 
/*
 | Variables    : string
 | Returns      : n/a
 | Description  : Set the NAG_URL
 *
 */
{
    $this->NAG_URL = $NURL;
}
/*_______________________________________________________________________*/


/*---------------------------------------------------------------------------
 | SMART CONTRACTS FUNCTIONS
 *---------------------------------------------------------------------------*/
/*_______________________________________________________________________*/
public function TestContract($Blockchain, $From, $Project) 
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
        "Blockchain" => $this->HexFix($Blockchain),
        "From"       => $this->HexFix($From),
        "Timestamp"  => $this->getFormattedTimestamp(),
        "Project"    => $this->StringToHex($Project),
        "Version"    => $this->version
    );

    return $this->fetch($this->NAG_URL . 'Circular_TestContract_', $data);
}
/*_______________________________________________________________________*/

/*_______________________________________________________________________*/
public function CallContract($Blockchain, $From, $Address, $Request) 
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
        "Blockchain" => $this->HexFix($Blockchain),
        "From"       => $this->HexFix($From),
        "Address"    => $this->HexFix($Address),
        "Request"    => $this->StringToHex($Request),
        "Timestamp"  => $this->getFormattedTimestamp(),
        "Version"    => $this->version
    );

    return $this->fetch($this->NAG_URL . 'Circular_CallContract_', $data);
}
/*_______________________________________________________________________*/


/*---------------------------------------------------------------------------
 | WALLET FUNCTIONS
 *---------------------------------------------------------------------------*/

    public function getWallet($blockchain, $address) {
        $blockchain = $this->hexFix($blockchain);
        $address    = $this->hexFix($address);
        $data       = array(
                            "Blockchain" => $blockchain,
                            "Address"    => $address,
			    "Version"    => $this->version
                      );
        return $this->fetch($this->NAG_URL . 'Circular_GetWallet_', $data);
    }

    public function registerWallet($blockchain, $privateKey) {
        $blockchain = $this->hexFix($blockchain);
        $privateKey = $this->hexFix($privateKey);
        $publicKey  = $this->getPublicKey($privateKey);
        $from       = hash('sha256', $publicKey);
        $to         = $from;
        $nonce      = '0';
        $type       = 'C_TYPE_REGISTERWALLET';
        $payloadObj = array(
                            "Action"    => "CP_REGISTERWALLET",
                            "PublicKey" => $publicKey,
			    "Version"   => $this->version
                      );
        $jsonstr    = json_encode($payloadObj);
        $payload    = $this->stringToHex($jsonstr);
        $timestamp  = $this->getFormattedTimestamp();
        $id         = hash('sha256', $from . $to . $payload . $nonce . $timestamp);
        $signature  = $this->signMessage($id, $privateKey);
        $res        = $this->sendTransaction($id, $from, $to, $timestamp, $type, $payload, $nonce, $publicKey, $signature, $blockchain);
        return $res;
    }

    public function getAsset($blockchain, $name) {
        $blockchain = $this->hexFix($blockchain);
        $data = array(
                      "Blockchain" => $blockchain,
                      "AssetName"  => $name,
                      "Version"    => $this->version
                     );
        return $this->fetch($this->NAG_URL . 'Circular_GetAsset_', $data);
    }

    public function getAssetSupply($blockchain, $name) {
        $blockchain = $this->hexFix($blockchain);
        $data = array(
                      "Blockchain" => $blockchain,
                      "AssetName"  => $name,
                      "Version"    => $this->version
                     );
        return $this->fetch($this->NAG_URL . 'Circular_GetAssetSupply_', $data);
    }

    public function getBlock($blockchain, $num) {
        $blockchain = $this->hexFix($blockchain);
        $data = array(
            "Blockchain"  => $blockchain,
            "BlockNumber" => $num,
            "Version"     => $this->version
        );
        return $this->fetch($this->NAG_URL . 'Circular_GetBlock_', $data);
    }

public function sendTransaction($id, $from, $to, $timestamp, $type, $payload, $nonce, $publicKey, $signature, $blockchain) {
    try {
        $from = $this->hexFix($from);
        $to = $this->hexFix($to);
        $publicKey = $this->hexFix($publicKey);
        $data = array(
            "ID"         => $id,
            "From"       => $from,
            "To"         => $to,
            "Timestamp"  => $timestamp,
            "Payload"    => $payload,
            "Nonce"      => $nonce,
            "Signature"  => $signature,
            "Blockchain" => $blockchain,
            "Type"       => $type,
            "Version"    => $this->version
        );
        $response = $this->fetch($this->NAG_URL . 'Circular_AddTransaction_', $data);
        return $response === false ? false : true;
    } catch (Exception $e) {
        $this->handleError($e);
        return false;
    }
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
