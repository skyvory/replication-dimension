<?PHP



// REQUESTING END
$responder = 'https://quantum.000webhostapp.com/relay/visual/index.php';

$url = $_GET['url'];

// $url = 'http://mathinsight.org/media/applet/image/large/curl_vector_field_sphere.png';
$fields = array(
	'url' => $url
	);
$postvars = '';
$sep = ' ';
foreach ($fields as $key => $value) {
	$postvars .= $sep.urlencode($key).'='.urlencode($value);
	$sep = '&';
}

$ch = curl_init();
$agent = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:10.0) Gecko/20100101 Firefox/10.0';

curl_setopt($ch, CURLOPT_URL, $responder);
curl_setopt($ch, CURLOPT_USERAGENT, $agent);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
curl_setopt($ch, CURLOPT_POST,1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cookie: __test=	d3270b1d6c82fdd24f6ac91393a15f2f"));

$result = curl_exec($ch);
if($result == false) {
	// echo"AAA";
	throw new Exception(curl_error($ch), curl_errno($ch));
}
curl_close($ch);
// var_dump($ch);
// $result = json_decode(trim($result), TRUE);

// header("Content-Type: image/png");
// echo $result;
var_dump($result);