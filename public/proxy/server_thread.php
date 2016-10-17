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
curl_setopt($ch, CURLOPT_USERAGENT, $agent);

$raw = curl_exec($ch);
curl_close( $ch );
if($raw == false) {
	throw new Exception(curl_error($ch), curl_errno($ch));
}

print $raw;

?>