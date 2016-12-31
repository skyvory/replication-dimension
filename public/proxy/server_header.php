<?php
error_reporting(0);
@ini_set('display_errors', 0);


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

$headers = get_headers($url);
echo json_encode($headers);