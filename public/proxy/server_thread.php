<?php
error_reporting(0);
@ini_set('display_errors', 0);

// THREAD REQUEST

if(isset($_POST['url'])) {
	$url = $_POST['url'];
}
else {
	$url = $_GET['url'];
}

if(isset($_POST['is_encrypted']) && $_POST['is_encrypted'] == 1) {
	$key = pack('H*', "16a6d7f49404004f737be38f9caec915a411a5380ea1604edbaf34ebc398f6a4");
	$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
	$ciphertext_dec = base64_decode($url);
	$iv_dec = substr($ciphertext_dec, 0, $iv_size);
	$ciphertext_dec = substr($ciphertext_dec, $iv_size);
	$url = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $ciphertext_dec, MCRYPT_MODE_CBC, $iv_dec);
	$url = base64_decode($url);
	$url = rtrim($url, "\0");
}

$agent = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:10.0) Gecko/20100101 Firefox/10.0';

$ch = curl_init( $url );
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_ENCODING ,"");
curl_setopt($ch, CURLOPT_USERAGENT, $agent);

$raw = curl_exec($ch);
if($raw == false) {
	throw new Exception(curl_error($ch), curl_errno($ch));
}

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($raw, 0, $header_size);
$body = substr($raw, $header_size);
curl_close( $ch );

if($http_code == 404) {
	header("HTTP/1.1 404 Not Found");
	// http_response_code(404);
}
print $body;

?>