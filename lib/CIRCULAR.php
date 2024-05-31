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
    public  $nagKey;
    public  $nagUrl;
    private $ec;


/*---------------------------------------------------------------------------
 | CLASS CONSTRUCT
 *---------------------------------------------------------------------------*/
    public function __construct() {
        $this->version   = '1.0.7';
        $this->lastError = NULL;
        $this->nagKey    = '';
        $this->nagUrl    = 'https://nag.circularlabs.io/NAG.php?cep=';
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
            'http'    => array(
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
 | DB Tables    : N/A
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

public function setNAGKey($NAGKey) {
    $this->NAG_KEY = $NAGKey;
}

public function setNAGURL($NURL='https://nag.circularlabs.io/NAG.php?cep=') {
    $this->NAG_URL = $NURL;
}





    public function getWallet($blockchain, $address) {
        $blockchain = $this->hexFix($blockchain);
        $address    = $this->hexFix($address);
        $data       = array(
                            "Blockchain" => $blockchain,
                            "Address"    => $address
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
                            "Action"     => "CP_REGISTERWALLET",
                             "PublicKey" => $publicKey
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
                      "AssetName"  => $name
                     );
        return $this->fetch($this->NAG_URL . 'Circular_GetAsset_', $data);
    }

    public function getAssetSupply($blockchain, $name) {
        $blockchain = $this->hexFix($blockchain);
        $data = array(
                      "Blockchain" => $blockchain,
                      "AssetName"  => $name
                     );
        return $this->fetch($this->NAG_URL . 'Circular_GetAssetSupply_', $data);
    }

    public function getBlock($blockchain, $num) {
        $blockchain = $this->hexFix($blockchain);
        $data = array(
            "Blockchain" => $blockchain,
            "BlockNumber" => $num
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
            "PublicKey"  => $publicKey,
            "Blockchain" => $blockchain,
            "Type"       => $type
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
