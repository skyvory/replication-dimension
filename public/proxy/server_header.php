<?php
// error_reporting(0);
// @ini_set('display_errors', 0);


if(isset($_POST['url'])) {
	$url = $_POST['url'];
}
else {
	$url = $_GET['url'];
}

$headers = get_headers($url);
echo json_encode($headers);