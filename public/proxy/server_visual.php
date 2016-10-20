<?php
error_reporting(0);
@ini_set('display_errors', 0);

// RESPONDING END

// IMAGE REQUEST

if(isset($_POST['url'])) {
	$url = $_POST['url'];
}
else {
	$url = $_GET['url'];
}

$ch = curl_init( $url );
$agent = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:10.0) Gecko/20100101 Firefox/10.0';

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
curl_setopt($ch, CURLOPT_VERBOSE, 1);
curl_setopt($ch, CURLOPT_USERAGENT, $agent);

$raw = curl_exec($ch);
if($raw == false) {
	throw new Exception(curl_error($ch), curl_errno($ch));
}

$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($raw, 0, $header_size);
$body = substr($raw, $header_size);

curl_close( $ch );


$encrypt_image = true;
if($encrypt_image) {
	$body = base64_encode($body);
	$key = pack('H*', "16a6d7f49404004f737be38f9caec915a411a5380ea1604edbaf34ebc398f6a4");
	$key_size = strlen($key);

	// create a random IV to use with CBC encoding
	$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
	$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	$ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $body, MCRYPT_MODE_CBC, $iv);
	// prepend the IV for it to be  available for decryption
	$ciphertext = $iv . $ciphertext;
	$ciphertext_base64 = base64_encode($ciphertext);

	if(false) {
		// start test decipher
		$key = pack('H*', "16a6d7f49404004f737be38f9caec915a411a5380ea1604edbaf34ebc398f6a4");
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
		$ciphertext_dec = base64_decode($ciphertext_base64);
		$iv_dec = substr($ciphertext_dec, 0, $iv_size);
		$ciphertext_dec = substr($ciphertext_dec, $iv_size);
		$raw = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $ciphertext_dec, MCRYPT_MODE_CBC, $iv_dec);
		print base64_decode($raw);
		return;
		// end test decipher
	}

	print $ciphertext_base64;
	return;
}


// header("Content-Type: image/png");
print $body;
return;
print $header;
return;



// broken response separation
// $matches = preg_split('/^\s*$/im', $chexec);
// $body = $matches[1] ;
// $body = implode("\n", array_slice(explode("\n", $body), 1));
// $header = $matches[0];
// print $header;
// header("Content-Type: image/png");
// print $body;
// return;



// header needed if directly showing image from php
// header("Content-Type: image/jpg");
// echo $raw;





?>
