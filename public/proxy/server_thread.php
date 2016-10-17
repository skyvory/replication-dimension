<?php
// error_reporting(0);
// @ini_set('display_errors', 0);

// THREAD REQUEST

if(isset($_POST['url'])) {
	$url = $_POST['url'];
}
else {
	$url = $_GET['url'];
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