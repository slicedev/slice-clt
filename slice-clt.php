<?php
include("api_functions.php");
ini_set('memory_limit', '-1');

// USAGE: php slice-clt.php METHOD PATH USERNAME PARAM1 VALUE1 PARAM2 VALUE2 ...
$key_file = getenv('KEY_FILE');
if (!isset($key_file) || $key_file == '') {
	$key_file = "myprivatekey.pem";
}
echo "HERE IS OUR KEY FILE $key_file\n";
if (!file_exists($key_file)) {
	abort("The private key file - $key_file - does not exist");
} else {
	set_key_file($key_file);
}

$base_uri = getenv('BASE_URI');
if (isset($base_uri) && $base_uri != '') {
	set_base_url($base_uri);
} else {
	abort("Please set base uri in the BASE_URI environment variable");
}

$use_proxy = getenv('USE_PROXY');
if (isset($use_proxy) && $use_proxy == 'true') {
	set_use_proxy(true);
} else {
	set_use_proxy(false);
}

$oauth_token = getenv('OAUTH_TOKEN');
if (isset($oauth_token) && $oauth_token != '') {
	set_oauth_token($oauth_token);
} else {
	$client_id = getenv('CLIENT_ID');
	if (isset($client_id) && $client_id != '') {
		set_client_id($client_id);
	} else {
		abort("Please set client_id in the CLIENT_ID environment variable");
	}
}

$request_timeout = getenv('REQUEST_TIMEOUT');
if (isset($request_timeout) && $request_timeout == 'true') {
	set_request_timeout($request_timeout);
}

$connect_timeout = getenv('CONNECT_TIMEOUT');
if (isset($connect_timeout) && $connect_timeout == 'true') {
	set_connect_timeout($connect_timeout);
}

$hide_response = getenv('HIDE_RESPONSE');

// DO NOT MODIFY ANYTHING BELOW THIS LINE
// -----------------------------------------------------

function abort($errorMsg)
{
	echo "$errorMsg\n";
	die();
}
function current_time()
{
	$mtime = microtime();
	$mtime = explode(' ', $mtime);
	$mtime = $mtime[1] + $mtime[0];
	return $mtime;
}

// 1. parse input params
if (count($argv) < 4) {
	echo "Required Usage: php slice-clt.php METHOD PATH USERNAME [PARAM1 VALUE1 [PARAM2 VALUE2 ... ]]\n";
	echo "Use empty string - \"\" - in place of USERNAME field - for requests that don't need username\n";
	abort("");
}

$n = 1;
$method = $argv[$n++];
$path = $argv[$n++];  // must be one of GET, POST, PUT, DELETE
$username = $argv[$n++];

$params = array();
while ($n+1 < count($argv))
{
	$paramValue = get_param_value($argv[$n+1]);
	$params[$argv[$n]] = $paramValue;
	$n+=2;
}

$locale = getenv('LOCALE');
if (isset($locale) && $locale != '') {
	$params['locale'] = $locale;
}

// 2. prepare request
$requestId = gethostname() . "-" . uniqid();
echo "--------------------------------------------------------------------\n";
echo "REQUEST ID IS: " . $requestId . "\n";
$params['reqId'] = $requestId;

$http_params = http_build_query($params);
#echo "HTTP PARAMS: " . $http_params . "\n";

// 3. make request
$start_time = current_time();

$response = slice_request($method, $path, $username, $http_params, $requestId);

// 4. echo output
$end_time = current_time();
echo "AUTH String: '" . $response['auth_hdr'] . "'\n";
echo "RESPONSE Code: " . $response['response_code'] . "\n";
echo "RESPONSE Content-Type: " . $response['content_type'] . "\n";

if (!(isset($hide_response) && $hide_response == 'true')) {
	$json_str = json_encode($response['response'], JSON_PRETTY_PRINT);
	if ($json_str == "null") {
		echo "RESPONSE TEXT:\n" . $response['response_text'] . "\n";
	} else {
		if (strpos($path, '/content') || json_encode($response['response'], JSON_PRETTY_PRINT) == "null") {
			echo "RESPONSE TEXT:\n";
			#echo $response['response_text'];
			echo strtok($response['response_text'], '\n');
			echo "\n";
		} else {
			echo "RESPONSE:\n" . $json_str . "\n";
		}
	}
}

echo "\n" . date("Y-m-d H:i:s T") . " ENCODING: " . trim($response['content_encoding']) . " RESPONSESIZE: " . $response['content_length'] . " TIME: " . round(($end_time - $start_time)*1000) . " ms. Request: " . $response['method'] . " " . $response['url'] . "\n";

?>
