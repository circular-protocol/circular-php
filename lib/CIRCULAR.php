<?php
use Elliptic\EC;

class Circular {
    private $NAG_KEY = '';
    private $NAG_URL = 'https://nag.circularlabs.io/NAG.php?cep=';
    private $ec;

    public function __construct() {
        $this->ec = new EC('secp256k1');
    }

    private function handleError($error) {
        error_log($error->getMessage());
    }

    private function padNumber($num) {
        return $num < 10 ? '0' . $num : $num;
    }

    private function getFormattedTimestamp() {
        $date = new DateTime();
        return $date->format('Y:m:d-H:i:s');
    }

    private function signMessage($message, $privateKey) {
        $key = $this->ec->keyFromPrivate($privateKey, 'hex');
        $msgHash = hash('sha256', $message);
        $signature = $key->sign($msgHash)->toDER('hex');
        return $signature;
    }

    private function verifySignature($publicKey, $message, $signature) {
        $key = $this->ec->keyFromPublic($publicKey, 'hex');
        $msgHash = hash('sha256', $message);
        return $key->verify($msgHash, $signature, 'hex');
    }

    private function getPublicKey($privateKey) {
        $key = $this->ec->keyFromPrivate($privateKey, 'hex');
        $publicKey = $key->getPublic('hex');
        return $publicKey;
    }

    private function stringToHex($str) {
        return bin2hex($str);
    }

    private function hexFix($word) {
        return ltrim($word, '0x');
    }

    public function setNAGKey($NAGKey) {
        $this->NAG_KEY = $NAGKey;
    }

    public function setNAGURL($NURL) {
        $this->NAG_URL = $NURL;
    }

    private function fetch($url, $data) {
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
            ),
        );
        $context  = stream_context_create($options);
        return file_get_contents($url, false, $context);
    }

    public function getWallet($blockchain, $address) {
        $blockchain = $this->hexFix($blockchain);
        $address = $this->hexFix($address);
        $data = array(
            "Blockchain" => $blockchain,
            "Address" => $address
        );
        return $this->fetch($this->NAG_URL . 'Circular_GetWallet_', $data);
    }

    public function registerWallet($blockchain, $privateKey) {
        $blockchain = $this->hexFix($blockchain);
        $privateKey = $this->hexFix($privateKey);
        $publicKey = $this->getPublicKey($privateKey);
        $from = hash('sha256', $publicKey);
        $to = $from;
        $nonce = '0';
        $type = 'C_TYPE_REGISTERWALLET';
        $payloadObj = array(
            "Action" => "CP_REGISTERWALLET",
            "PublicKey" => $publicKey
        );
        $jsonstr = json_encode($payloadObj);
        $payload = $this->stringToHex($jsonstr);
        $timestamp = $this->getFormattedTimestamp();
        $id = hash('sha256', $from . $to . $payload . $nonce . $timestamp);
        $signature = $this->signMessage($id, $privateKey);
        $res = $this->sendTransaction($id, $from, $to, $timestamp, $type, $payload, $nonce, $publicKey, $signature, $blockchain);
        return $res;
    }

    public function getAsset($blockchain, $name) {
        $blockchain = $this->hexFix($blockchain);
        $data = array(
            "Blockchain" => $blockchain,
            "AssetName" => $name
        );
        return $this->fetch($this->NAG_URL . 'Circular_GetAsset_', $data);
    }

    public function getAssetSupply($blockchain, $name) {
        $blockchain = $this->hexFix($blockchain);
        $data = array(
            "Blockchain" => $blockchain,
            "AssetName" => $name
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
                "ID" => $id,
                "From" => $from,
                "To" => $to,
                "Timestamp" => $timestamp,
                "Payload" => $payload,
                "Nonce" => $nonce,
                "Signature" => $signature,
                "PublicKey" => $publicKey,
                "Blockchain" => $blockchain,
                "Type" => $type
            );
            $response = $this->fetch($this->NAG_URL . 'Circular_AddTransaction_', $data);
            return $response === false ? false : true;
        } catch (Exception $e) {
            $this->handleError($e);
            return false;
        }
    }
}
?>
