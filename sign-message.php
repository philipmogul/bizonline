<?php 
header("Access-Control-Allow-Origin: *");

require __DIR__ . '/vendor/autoload.php' ;

use phpseclib\Crypt\RSA;

// include('phpseclib/Crypt/RSA.php');

// require_once('phpseclib/Crypt/RSA.php');

// Sample key.  Replace with one used for CSR generation
$KEY = 'key.pem';
//$PASS = 'S3cur3P@ssw0rd';

$req = $_GET['request'];
$privateKey = openssl_get_privatekey(file_get_contents($KEY) /*, $PASS */);

$signature = null;
openssl_sign($req, $signature, $privateKey);

// Or alternately, via phpseclib

// $rsa = new RSA();
// $rsa->loadKey(file_get_contents($KEY));
// $rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
// $signature = $rsa->sign($req);

if ($signature) {
	header("Content-type: text/plain");
	echo base64_encode($signature);
	exit(0);
}

echo '<h1>Error signing message</h1>';
exit(1);


?>