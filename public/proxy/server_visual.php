<?php

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

curl_close( $ch );


// header needed if directly showing image from php
// header("Content-Type: image/jpg");
// echo $raw;





?>
