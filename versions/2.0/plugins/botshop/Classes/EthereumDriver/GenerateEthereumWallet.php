<?php

require_once "vendor/autoload.php";

use Sop\CryptoTypes\Asymmetric\EC\ECPublicKey;
use Sop\CryptoTypes\Asymmetric\EC\ECPrivateKey;
use Sop\CryptoEncoding\PEM;
use kornrunner\Keccak;

class EthereumDriver{


    private $isTestnet = true;


    private $testnetAPI = "https://api-ropsten.etherscan.io";
    private $mainnetAPI = "https://api.etherscan.io";


    function wei2eth($wei){
        return bcdiv($wei,'1000000000000000000',18);
    }
    function ethdecode($input){
        return dechex($input);
    }

    function getBalance($address){
        $apiPoint = $this->mainnetAPI;
        if($this->isTestnet){
            $apiPoint = $this->testnetAPI;
        }

        return json_decode(file_get_contents($apiPoint."/api?module=account&action=balance&address=".$address."&tag=latest"),true);
    }

    function getNewWallet($testnet)
    {
        $config = [
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'secp256k1'
        ];

        $res = openssl_pkey_new($config);

        // Generate Private Key
        openssl_pkey_export($res, $prev_key);

        // Get The Public Key
//        $key_detail = openssl_pkey_get_details($res);
//        $pub_key = $key_detail["key"];
        $priv_pem = PEM::fromString($prev_key);

        // Convert to Elliptic Curve Private Key Format
        $ec_priv_key = ECPrivateKey::fromPEM($priv_pem);

        // Then convert it to ASN1 Structure
        $ec_priv_seq = $ec_priv_key->toASN1();

        // Private Key & Public Key in HEX
        $priv_key_hex = bin2hex($ec_priv_seq->at(1)->asOctetString()->string());
        $pub_key_hex = bin2hex($ec_priv_seq->at(3)->asTagged()->asExplicit()->asBitString()->string());


        // Derive the Ethereum Address from public key
        // Every EC public key will always start with 0x04,
        // we need to remove the leading 0x04 in order to hash it correctly
        $pub_key_hex_2 = substr($pub_key_hex, 2);


        // Hash time
        $hash = Keccak::hash(hex2bin($pub_key_hex_2), 256);


        // Ethereum address has 20 bytes length. (40 hex characters long)
        // We only need the last 20 bytes as Ethereum address
        $wallet_address = '0x' . substr($hash, -40);
        $wallet_private_key = '0x' . $priv_key_hex;
        $wallet_pubic_key = '0x' . $pub_key_hex;

        return array("address" => $wallet_address, "privatekey" => $wallet_private_key.":".$wallet_pubic_key, "pubic_key" => $wallet_pubic_key);
    }

    function CreateETHPrice($amount) {
        $eth = 0;
        $json = json_decode(file_get_contents("https://min-api.cryptocompare.com/data/generateAvg?fsym=ETH&tsym=USD&e=Coinbase"));
        foreach($json as $obj) {
            if ($obj->TOSYMBOL == 'USD')  {
                $eth = $obj->PRICE;
            }
        }

        $USD = 1 / $eth;
        return round($USD * $amount, 6);
    }


    /*
    $driver = new EthereumDriver();
    //var_dump($driver->getNewWallet());
    $ethereumOnWallet = $driver->getBalance("0xAd8615f74fCbb5Ddee0A8F5761a23a2A26aA8ec5")["result"];
    echo $driver->wei2eth($ethereumOnWallet) ;

    */
}


//$value = "10";

//echo $value * 0.10;



